<?php

namespace Tests\Unit\Listeners;

use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use App\Jobs\RecalculateMetricsJob;
use App\Listeners\StudySession\DispatchMetricsRecalculation;
use App\Models\StudySession;
use App\Services\RedisLuaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DispatchMetricsRecalculationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dispatches_job_on_created_event(): void
    {
        Queue::fake();

        $userId = (string) Str::uuid();
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = $userId;

        $luaService = Mockery::mock(RedisLuaService::class);
        $luaService->shouldReceive('callScript')
            ->with('job_dedup', ["job_lock:metrics:{$userId}"], [10])
            ->once()
            ->andReturn(1);

        $listener = new DispatchMetricsRecalculation($luaService);
        $listener->handle(new StudySessionCreated($session));

        Queue::assertPushed(RecalculateMetricsJob::class, function ($job) use ($userId) {
            return $job->userId === $userId && $job->fullRecalc === true;
        });
    }

    public function test_dispatches_full_recalc_on_deleted_event(): void
    {
        Queue::fake();

        $userId = (string) Str::uuid();
        $event = new StudySessionDeleted($userId, (string) Str::uuid(), 60, Carbon::now());

        $luaService = Mockery::mock(RedisLuaService::class);
        $luaService->shouldReceive('callScript')->once()->andReturn(1);

        $listener = new DispatchMetricsRecalculation($luaService);
        $listener->handle($event);

        Queue::assertPushed(RecalculateMetricsJob::class, function ($job) use ($userId) {
            return $job->userId === $userId && $job->fullRecalc === true;
        });
    }

    public function test_dispatches_recalc_on_update_with_relevant_fields(): void
    {
        Queue::fake();

        $userId = (string) Str::uuid();
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = $userId;

        $luaService = Mockery::mock(RedisLuaService::class);
        $luaService->shouldReceive('callScript')->once()->andReturn(1);

        $listener = new DispatchMetricsRecalculation($luaService);
        $listener->handle(new StudySessionUpdated($session, ['ended_at']));

        Queue::assertPushed(RecalculateMetricsJob::class, function ($job) use ($userId) {
            return $job->userId === $userId && $job->fullRecalc === true;
        });
    }

    public function test_skips_full_recalc_on_update_with_irrelevant_fields(): void
    {
        Queue::fake();

        $userId = (string) Str::uuid();
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = $userId;

        $luaService = Mockery::mock(RedisLuaService::class);
        $luaService->shouldReceive('callScript')->once()->andReturn(1);

        $listener = new DispatchMetricsRecalculation($luaService);
        $listener->handle(new StudySessionUpdated($session, ['notes']));

        Queue::assertPushed(RecalculateMetricsJob::class, function ($job) use ($userId) {
            return $job->userId === $userId && $job->fullRecalc === false;
        });
    }

    public function test_dispatches_even_when_lua_fails(): void
    {
        Queue::fake();

        $userId = (string) Str::uuid();
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = $userId;

        $luaService = Mockery::mock(RedisLuaService::class);
        $luaService->shouldReceive('callScript')
            ->once()
            ->andThrow(new RuntimeException('Redis down'));

        $listener = new DispatchMetricsRecalculation($luaService);
        $listener->handle(new StudySessionCreated($session));

        Queue::assertPushed(RecalculateMetricsJob::class, function ($job) use ($userId) {
            return $job->userId === $userId;
        });
    }

    public function test_respects_lua_dedup_when_available(): void
    {
        Queue::fake();

        $userId = (string) Str::uuid();
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = $userId;

        $luaService = Mockery::mock(RedisLuaService::class);
        $luaService->shouldReceive('callScript')->once()->andReturn(0);

        $listener = new DispatchMetricsRecalculation($luaService);
        $listener->handle(new StudySessionCreated($session));

        Queue::assertNotPushed(RecalculateMetricsJob::class);
    }
}
