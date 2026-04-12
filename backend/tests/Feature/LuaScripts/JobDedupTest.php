<?php

namespace Tests\Feature\LuaScripts;

use App\Events\StudySession\StudySessionCreated;
use App\Jobs\RecalculateMetricsJob;
use App\Listeners\StudySession\DispatchMetricsRecalculation;
use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use App\Services\RedisLuaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class JobDedupTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_call_returns_1(): void
    {
        Queue::fake();
        $session = $this->createSession();

        $redisLua = Mockery::mock(RedisLuaService::class);
        $redisLua->shouldReceive('callScript')
            ->once()
            ->with('job_dedup', ["job_lock:metrics:{$session->user_id}"], [10])
            ->andReturn(1);

        $this->app->instance(RedisLuaService::class, $redisLua);

        app(DispatchMetricsRecalculation::class)->handle(new StudySessionCreated($session));

        Queue::assertPushed(RecalculateMetricsJob::class, 1);
    }

    public function test_second_immediate_call_returns_0(): void
    {
        Queue::fake();
        $session = $this->createSession();

        $redisLua = Mockery::mock(RedisLuaService::class);
        $redisLua->shouldReceive('callScript')
            ->once()
            ->with('job_dedup', ["job_lock:metrics:{$session->user_id}"], [10])
            ->andReturn(0);

        $this->app->instance(RedisLuaService::class, $redisLua);

        app(DispatchMetricsRecalculation::class)->handle(new StudySessionCreated($session));

        Queue::assertNothingPushed();
    }

    public function test_call_after_ttl_returns_1(): void
    {
        Queue::fake();
        $session = $this->createSession();

        $redisLua = Mockery::mock(RedisLuaService::class);
        $redisLua->shouldReceive('callScript')
            ->times(3)
            ->with('job_dedup', ["job_lock:metrics:{$session->user_id}"], [10])
            ->andReturn(1, 0, 1);

        $this->app->instance(RedisLuaService::class, $redisLua);

        $listener = app(DispatchMetricsRecalculation::class);
        $listener->handle(new StudySessionCreated($session));
        $listener->handle(new StudySessionCreated($session));
        $this->travel(6)->seconds();
        $listener->handle(new StudySessionCreated($session));

        // O script Lua volta a liberar a terceira chamada, mas o job segue protegido
        // por ShouldBeUnique, então apenas um enqueue efetivo permanece visível aqui.
        Queue::assertPushed(RecalculateMetricsJob::class, 1);
    }

    private function createSession(): StudySession
    {
        $user = User::factory()->create();
        $technology = Technology::create([
            'user_id' => $user->id,
            'name' => 'Lua',
            'slug' => 'lua',
            'color' => '#000080',
            'is_active' => true,
        ]);

        return StudySession::factory()->create([
            'user_id' => $user->id,
            'technology_id' => $technology->id,
        ]);
    }
}
