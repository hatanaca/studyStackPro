<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SlidingWindowRateLimit;
use App\Models\User;
use App\Services\RedisLuaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class SlidingWindowRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function decodeJson(JsonResponse $response): array
    {
        return json_decode($response->getContent(), true) ?? [];
    }

    public function test_allows_request_when_lua_returns_allowed(): void
    {
        $redisLuaService = \Mockery::mock(RedisLuaService::class);
        $redisLuaService->shouldReceive('callScript')
            ->once()
            ->andReturn([1, 0]);

        $middleware = new SlidingWindowRateLimit($redisLuaService);

        $request = Request::create('/api/v1/test', 'GET');
        $request->setUserResolver(fn () => $this->user);

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(60, $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_blocks_request_when_lua_returns_blocked(): void
    {
        $redisLuaService = \Mockery::mock(RedisLuaService::class);
        $redisLuaService->shouldReceive('callScript')
            ->once()
            ->andReturn([0, 30]);

        $middleware = new SlidingWindowRateLimit($redisLuaService);

        $request = Request::create('/api/v1/test', 'GET');
        $request->setUserResolver(fn () => $this->user);

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(429, $response->getStatusCode());
        $data = $this->decodeJson($response);
        $this->assertEquals('RATE_LIMITED', $data['error']['code']);
        $this->assertNotNull($response->headers->get('Retry-After'));
    }

    public function test_returns_503_when_redis_fails_and_fail_open_is_false(): void
    {
        config(['services.rate_limit.fail_open' => false]);

        $redisLuaService = \Mockery::mock(RedisLuaService::class);
        $redisLuaService->shouldReceive('callScript')
            ->once()
            ->andThrow(new \RuntimeException('Connection refused'));

        $middleware = new SlidingWindowRateLimit($redisLuaService);

        $request = Request::create('/api/v1/test', 'GET');
        $request->setUserResolver(fn () => $this->user);

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(503, $response->getStatusCode());
        $data = $this->decodeJson($response);
        $this->assertEquals('SERVICE_UNAVAILABLE', $data['error']['code']);
    }

    public function test_allows_request_when_redis_fails_and_fail_open_is_true(): void
    {
        config(['services.rate_limit.fail_open' => true]);

        $redisLuaService = \Mockery::mock(RedisLuaService::class);
        $redisLuaService->shouldReceive('callScript')
            ->once()
            ->andThrow(new \RuntimeException('Connection refused'));

        $middleware = new SlidingWindowRateLimit($redisLuaService);

        $request = Request::create('/api/v1/test', 'GET');
        $request->setUserResolver(fn () => $this->user);

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_enforces_minimum_limit_of_one(): void
    {
        $redisLuaService = \Mockery::mock(RedisLuaService::class);
        $redisLuaService->shouldReceive('callScript')
            ->once()
            ->andReturn([1, 0]);

        $middleware = new SlidingWindowRateLimit($redisLuaService);

        $request = Request::create('/api/v1/test', 'GET');
        $request->setUserResolver(fn () => $this->user);

        $response = $middleware->handle($request, fn () => response()->json(['ok' => true]), 0);

        $this->assertEquals(1, $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_uses_ip_for_anonymous_users(): void
    {
        $redisLuaService = \Mockery::mock(RedisLuaService::class);
        $redisLuaService->shouldReceive('callScript')
            ->once()
            ->andReturnUsing(function ($script, $keys, $args) {
                $this->assertStringContainsString($args[0], $keys[0]);
                return [1, 0];
            });

        $middleware = new SlidingWindowRateLimit($redisLuaService);

        $request = Request::create('/api/v1/test', 'GET');

        $middleware->handle($request, fn () => response()->json(['ok' => true]));
    }
}
