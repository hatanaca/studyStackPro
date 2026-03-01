<?php

namespace Tests\Unit;

use App\Events\Analytics\MetricsRecalculating;
use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionUpdated;
use App\Listeners\StudySession\BroadcastMetricsRecalculating;
use App\Models\StudySession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class BroadcastMetricsRecalculatingTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_dispatches_metrics_recalculating_on_session_created(): void
    {
        Event::fake([MetricsRecalculating::class]);

        $userId = (string) Str::uuid();
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = $userId;

        $event = new StudySessionCreated($session);
        $listener = new BroadcastMetricsRecalculating;
        $listener->handle($event);

        Event::assertDispatched(MetricsRecalculating::class, function ($e) use ($userId) {
            return $e->userId === $userId;
        });
    }

    public function test_dispatches_metrics_recalculating_on_session_updated(): void
    {
        Event::fake([MetricsRecalculating::class]);

        $userId = (string) Str::uuid();
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = $userId;

        $event = new StudySessionUpdated($session, ['ended_at']);
        $listener = new BroadcastMetricsRecalculating;
        $listener->handle($event);

        Event::assertDispatched(MetricsRecalculating::class, function ($e) use ($userId) {
            return $e->userId === $userId;
        });
    }
}
