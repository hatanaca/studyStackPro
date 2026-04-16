<?php

namespace Tests\Integration\Lua;

use Illuminate\Support\Facades\Redis;
use Tests\Concerns\InteractsWithRealRedis;
use Tests\TestCase;

/**
 * Executes job_dedup.lua against a real Redis instance.
 */
class JobDedupLuaIntegrationTest extends TestCase
{
    use InteractsWithRealRedis;

    private string $script;

    protected function setUp(): void
    {
        parent::setUp();

        $path = base_path('../redis-scripts/job_dedup.lua');
        $content = file_get_contents($path);
        if ($content === false) {
            $this->markTestSkipped("Script Lua não encontrado em: {$path}");
        }
        $this->script = $content;

        $this->skipUnlessRedisIntegrationProbe(function () {
            Redis::flushdb();
            Redis::eval($this->script, 1, 'dedup:test:probe', 5);
        });
    }

    protected function tearDown(): void
    {
        try {
            Redis::flushdb();
        } catch (\Throwable) {
        }
        parent::tearDown();
    }

    public function test_first_call_acquires_lock(): void
    {
        $result = Redis::eval($this->script, 1, 'dedup:test:job1', 5);

        $this->assertSame(1, (int) $result);
        $this->assertSame('1', Redis::get('dedup:test:job1'));
    }

    public function test_second_call_is_rejected(): void
    {
        Redis::eval($this->script, 1, 'dedup:test:job2', 5);
        $result = Redis::eval($this->script, 1, 'dedup:test:job2', 5);

        $this->assertSame(0, (int) $result);
    }

    public function test_lock_has_ttl(): void
    {
        Redis::eval($this->script, 1, 'dedup:test:job3', 10);

        $ttl = Redis::ttl('dedup:test:job3');
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(10, $ttl);
    }

    public function test_different_keys_are_independent(): void
    {
        $r1 = Redis::eval($this->script, 1, 'dedup:test:a', 5);
        $r2 = Redis::eval($this->script, 1, 'dedup:test:b', 5);

        $this->assertSame(1, (int) $r1);
        $this->assertSame(1, (int) $r2);
    }
}
