<?php

namespace Tests\Unit\Listeners;

use App\Events\StudySession\SessionEnded;
use App\Events\StudySession\StudySessionUpdated;
use App\Listeners\StudySession\BroadcastSessionEnded;
use App\Models\StudySession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class BroadcastSessionEndedTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_fires_session_ended_when_ended_at_changed_and_not_null(): void
    {
        Event::fake([SessionEnded::class]);

        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = (string) Str::uuid();
        $session->ended_at = Carbon::now();

        $listener = new BroadcastSessionEnded;
        $listener->handle(new StudySessionUpdated($session, ['ended_at']));

        Event::assertDispatched(SessionEnded::class);
    }

    public function test_does_not_fire_when_ended_at_not_in_changed_fields(): void
    {
        Event::fake([SessionEnded::class]);

        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = (string) Str::uuid();
        $session->ended_at = Carbon::now();

        $listener = new BroadcastSessionEnded;
        $listener->handle(new StudySessionUpdated($session, ['notes']));

        Event::assertNotDispatched(SessionEnded::class);
    }

    public function test_does_not_fire_when_ended_at_is_null(): void
    {
        Event::fake([SessionEnded::class]);

        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = (string) Str::uuid();
        $session->ended_at = null;

        $listener = new BroadcastSessionEnded;
        $listener->handle(new StudySessionUpdated($session, ['ended_at']));

        Event::assertNotDispatched(SessionEnded::class);
    }
}
