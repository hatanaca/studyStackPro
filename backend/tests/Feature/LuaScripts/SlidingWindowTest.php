<?php

namespace Tests\Feature\LuaScripts;

use App\Models\Technology;
use App\Models\User;
use App\Services\RedisLuaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SlidingWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_requests_within_limit_are_allowed(): void
    {
        Event::fake();
        Queue::fake();

        [$user, $token, $technology] = $this->createAuthenticatedContext();

        $redisLua = Mockery::mock(RedisLuaService::class);
        $redisLua->shouldReceive('callScript')
            ->once()
            ->withArgs(fn (string $name, array $keys, array $args) => $name === 'sliding_window'
                && str_starts_with($keys[0], "rate:{$user->id}:api/v1/study-sessions")
                && (int) $args[1] === 60000
                && (int) $args[2] === 30)
            ->andReturn([1, 0]);

        $this->app->instance(RedisLuaService::class, $redisLua);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $technology->id,
                'started_at' => Carbon::now()->subHour()->toIso8601String(),
                'ended_at' => Carbon::now()->toIso8601String(),
            ]);

        $response->assertStatus(201)
            ->assertHeader('X-RateLimit-Limit', '30');
    }

    public function test_request_exceeding_limit_is_blocked(): void
    {
        [$user, $token] = $this->createAuthenticatedContext();

        $redisLua = Mockery::mock(RedisLuaService::class);
        $redisLua->shouldReceive('callScript')
            ->once()
            ->withArgs(fn (string $name, array $keys, array $args) => $name === 'sliding_window'
                && str_starts_with($keys[0], "rate:{$user->id}:api/v1/study-sessions")
                && (int) $args[2] === 30)
            ->andReturn([0, 12]);

        $this->app->instance(RedisLuaService::class, $redisLua);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions', []);

        $response->assertStatus(429)
            ->assertHeader('Retry-After', '12')
            ->assertHeader('X-RateLimit-Limit', '30');
    }

    public function test_window_resets_after_expiry(): void
    {
        Event::fake();
        Queue::fake();

        [, $token, $technology] = $this->createAuthenticatedContext();

        $redisLua = Mockery::mock(RedisLuaService::class);
        $redisLua->shouldReceive('callScript')
            ->twice()
            ->andReturn([0, 1], [1, 0]);

        $this->app->instance(RedisLuaService::class, $redisLua);

        $blocked = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions', []);

        $allowed = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $technology->id,
                'started_at' => Carbon::now()->subMinutes(50)->toIso8601String(),
                'ended_at' => Carbon::now()->toIso8601String(),
            ]);

        $blocked->assertStatus(429);
        $allowed->assertStatus(201);
    }

    /**
     * @return array{0: User, 1: string, 2: Technology}
     */
    private function createAuthenticatedContext(): array
    {
        $user = User::factory()->create();
        $technology = Technology::create([
            'user_id' => $user->id,
            'name' => 'Redis',
            'slug' => 'redis',
            'color' => '#d82c20',
            'is_active' => true,
        ]);

        return [$user, $user->createToken('api-token')->plainTextToken, $technology];
    }
}
