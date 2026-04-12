<?php

namespace Tests\Unit\Listeners;

use App\Events\StudySession\SessionStarted;
use App\Events\StudySession\StudySessionCreated;
use App\Listeners\StudySession\BroadcastSessionStarted;
use App\Models\StudySession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class BroadcastSessionStartedTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_fires_session_started_when_session_has_no_ended_at(): void
    {
        Event::fake([SessionStarted::class]);

        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = (string) Str::uuid();
        $session->ended_at = null;

        $listener = new BroadcastSessionStarted;
        $listener->handle(new StudySessionCreated($session));

        Event::assertDispatched(SessionStarted::class);
    }

    public function test_does_not_fire_when_session_is_completed(): void
    {
        Event::fake([SessionStarted::class]);

        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->user_id = (string) Str::uuid();
        $session->ended_at = Carbon::now();

        $listener = new BroadcastSessionStarted;
        $listener->handle(new StudySessionCreated($session));

        Event::assertNotDispatched(SessionStarted::class);
    }
}
