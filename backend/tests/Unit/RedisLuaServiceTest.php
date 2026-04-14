<?php

namespace Tests\Unit;

use App\Services\RedisLuaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RedisLuaServiceTest extends TestCase
{
    private RedisLuaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RedisLuaService;
    }

    public function test_scripts_returns_three_lua_scripts(): void
    {
        $scripts = $this->service->scripts();

        $this->assertCount(3, $scripts);
        $this->assertArrayHasKey('job_dedup', $scripts);
        $this->assertArrayHasKey('sliding_window', $scripts);
        $this->assertArrayHasKey('streak_update', $scripts);
    }

    public function test_scripts_paths_point_to_lua_files(): void
    {
        foreach ($this->service->scripts() as $name => $path) {
            $this->assertStringEndsWith('.lua', $path, "Script '{$name}' path must end with .lua");
        }
    }

    /**
     * SHA ausente → loadScripts é chamado → segunda leitura retorna SHA → evalsha executado.
     * Usa `ordered()` para garantir sequência correta de chamadas ao mesmo método.
     */
    public function test_call_script_loads_scripts_when_sha_not_cached(): void
    {
        $service = Mockery::mock(RedisLuaService::class)->makePartial();
        $service->shouldReceive('loadScripts')->once();
        $connection = Mockery::mock();
        $client = Mockery::mock();

        Cache::shouldReceive('get')
            ->with('lua_sha:job_dedup')
            ->twice()
            ->andReturn(null, 'fake_sha');

        Redis::shouldReceive('connection')
            ->once()
            ->andReturn($connection);

        $connection->shouldReceive('client')
            ->once()
            ->andReturn($client);

        $client->shouldReceive('evalsha')
            ->once()
            ->with('fake_sha', ['key', 5], 1)
            ->andReturn(1);

        $result = $service->callScript('job_dedup', ['key'], [5]);

        $this->assertSame(1, $result);
    }

    /**
     * evalsha lança NOSCRIPT → loadScripts chamado → SHA recarregado → evalsha reexecutado.
     */
    public function test_call_script_retries_on_noscript_error(): void
    {
        $service = Mockery::mock(RedisLuaService::class)->makePartial();
        $service->shouldReceive('loadScripts')->once();
        $connection = Mockery::mock();
        $client = Mockery::mock();

        Cache::shouldReceive('get')
            ->with('lua_sha:streak_update')
            ->andReturn('stale_sha', 'new_sha');

        Redis::shouldReceive('connection')
            ->twice()
            ->andReturn($connection);

        $connection->shouldReceive('client')
            ->twice()
            ->andReturn($client);

        $client->shouldReceive('evalsha')
            ->once()
            ->with('stale_sha', ['k1', 'today'], 1)
            ->andThrow(new \Exception('NOSCRIPT No matching script'));

        $client->shouldReceive('evalsha')
            ->once()
            ->with('new_sha', ['k1', 'today'], 1)
            ->andReturn(1);

        $result = $service->callScript('streak_update', ['k1'], ['today']);

        $this->assertSame(1, $result);
    }

    /**
     * Erros que não são NOSCRIPT devem ser relançados imediatamente.
     */
    public function test_call_script_throws_non_noscript_errors(): void
    {
        $connection = Mockery::mock();
        $client = Mockery::mock();

        Cache::shouldReceive('get')
            ->with('lua_sha:job_dedup')
            ->andReturn('some_sha');

        Redis::shouldReceive('connection')
            ->once()
            ->andReturn($connection);

        $connection->shouldReceive('client')
            ->once()
            ->andReturn($client);

        $client->shouldReceive('evalsha')
            ->once()
            ->andThrow(new \Exception('ERR wrong number of arguments'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ERR wrong number of arguments');

        $this->service->callScript('job_dedup', ['key'], [5]);
    }

    /**
     * Após retry NOSCRIPT, se SHA ainda for null, RuntimeException deve ser lançada.
     */
    public function test_call_script_throws_when_sha_still_null_after_noscript_retry(): void
    {
        $service = Mockery::mock(RedisLuaService::class)->makePartial();
        $service->shouldReceive('loadScripts');
        $connection = Mockery::mock();
        $client = Mockery::mock();

        Cache::shouldReceive('get')
            ->with('lua_sha:job_dedup')
            ->andReturn('stale_sha', null);

        Redis::shouldReceive('connection')
            ->once()
            ->andReturn($connection);

        $connection->shouldReceive('client')
            ->once()
            ->andReturn($client);

        $client->shouldReceive('evalsha')
            ->once()
            ->andThrow(new \Exception('NOSCRIPT No matching script'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SHA do script Lua [job_dedup] não disponível após recarregamento.');

        $service->callScript('job_dedup', ['key'], [5]);
    }

    public function test_cache_key_format(): void
    {
        $reflection = new \ReflectionMethod(RedisLuaService::class, 'cacheKey');
        $reflection->setAccessible(true);

        $key = $reflection->invoke($this->service, 'streak_update');

        $this->assertSame('lua_sha:streak_update', $key);
    }

    public function test_is_noscript_exception_detection(): void
    {
        $reflection = new \ReflectionMethod(RedisLuaService::class, 'isNoScriptException');
        $reflection->setAccessible(true);

        $this->assertTrue($reflection->invoke($this->service, new \Exception('NOSCRIPT No matching script')));
        $this->assertTrue($reflection->invoke($this->service, new \Exception(' NOSCRIPT trimmed')));
        $this->assertFalse($reflection->invoke($this->service, new \Exception('ERR wrong number of arguments')));
        $this->assertFalse($reflection->invoke($this->service, new \Exception('Connection refused')));
    }
}
