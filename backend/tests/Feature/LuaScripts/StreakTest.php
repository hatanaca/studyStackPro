<?php

namespace Tests\Feature\LuaScripts;

use App\Models\User;
use App\Services\RedisLuaService;
use App\Services\StreakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_session_sets_streak_to_1(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        $redisLua = Mockery::mock(RedisLuaService::class);
        $redisLua->shouldReceive('callScript')
            ->once()
            ->withArgs(fn (string $name, array $keys, array $args) => $name === 'streak_update'
                && $keys === ["streak:user:{$user->id}", "streak:last_day:{$user->id}"]
                && count($args) === 2)
            ->andReturn(1);

        $service = new StreakService($redisLua);

        $this->assertSame(1, $service->update($user->id));
    }

    public function test_consecutive_day_increments_streak(): void
    {
        $user = User::factory()->create(['timezone' => 'America/Sao_Paulo']);

        $redisLua = Mockery::mock(RedisLuaService::class);
        $redisLua->shouldReceive('callScript')->once()->andReturn(2);

        $service = new StreakService($redisLua);

        $this->assertSame(2, $service->update($user->id));
    }

    public function test_gap_resets_streak_to_1(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        $redisLua = Mockery::mock(RedisLuaService::class);
        $redisLua->shouldReceive('callScript')->once()->andReturn(1);

        $service = new StreakService($redisLua);

        $this->assertSame(1, $service->update($user->id));
    }

    public function test_same_day_does_not_increment(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        $redisLua = Mockery::mock(RedisLuaService::class);
        $redisLua->shouldReceive('callScript')->once()->andReturn(4);

        $service = new StreakService($redisLua);

        $this->assertSame(4, $service->update($user->id));
    }
}
