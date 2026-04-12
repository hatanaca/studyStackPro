<?php

namespace Tests\Unit\Events;

use App\Events\StudySession\SessionStarted;
use App\Models\StudySession;
use Carbon\Carbon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class SessionStartedTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_broadcasts_on_dashboard_channel(): void
    {
        $userId = (string) Str::uuid();

        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->shouldReceive('loadMissing')->with('technology')->andReturnSelf();
        $session->user_id = $userId;

        $event = new SessionStarted($session);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);

        $expected = new PrivateChannel('dashboard.'.$userId);
        $this->assertEquals($expected->name, $channels[0]->name);
    }

    public function test_broadcast_as_returns_session_started(): void
    {
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->shouldReceive('loadMissing')->with('technology')->andReturnSelf();
        $session->user_id = (string) Str::uuid();

        $event = new SessionStarted($session);

        $this->assertEquals('.session.started', $event->broadcastAs());
    }

    public function test_broadcast_with_contains_session_data(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 12:00:00'));

        $userId = (string) Str::uuid();
        $sessionId = (string) Str::uuid();
        $startedAt = Carbon::parse('2026-04-06 11:30:00');

        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->shouldReceive('loadMissing')->with('technology')->andReturnSelf();
        $session->user_id = $userId;
        $session->id = $sessionId;
        $session->started_at = $startedAt;
        $session->technology = null;

        $event = new SessionStarted($session);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('session', $payload);
        $sessionData = $payload['session'];
        $this->assertEquals($sessionId, $sessionData['id']);
        $this->assertNull($sessionData['technology']);
        $this->assertEquals($startedAt->toIso8601String(), $sessionData['started_at']);
        $this->assertNull($sessionData['ended_at']);
        $this->assertArrayHasKey('elapsed_seconds', $sessionData);
        $this->assertEquals(1800, $sessionData['elapsed_seconds']);
    }
}
