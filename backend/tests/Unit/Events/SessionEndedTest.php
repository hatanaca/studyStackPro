<?php

namespace Tests\Unit\Events;

use App\Events\StudySession\SessionEnded;
use App\Models\StudySession;
use Carbon\Carbon;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class SessionEndedTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_broadcasts_on_dashboard_channel(): void
    {
        $userId = (string) Str::uuid();

        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->shouldReceive('loadMissing')->with('technology')->andReturnSelf();
        $session->user_id = $userId;

        $event = new SessionEnded($session);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);

        $expected = new PrivateChannel('dashboard.'.$userId);
        $this->assertEquals($expected->name, $channels[0]->name);
    }

    public function test_broadcast_as_returns_session_ended(): void
    {
        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->shouldReceive('loadMissing')->with('technology')->andReturnSelf();
        $session->user_id = (string) Str::uuid();

        $event = new SessionEnded($session);

        $this->assertEquals('.session.ended', $event->broadcastAs());
    }

    public function test_broadcast_with_contains_session_data(): void
    {
        $sessionId = (string) Str::uuid();
        $startedAt = Carbon::parse('2026-04-06 10:00:00');
        $endedAt = Carbon::parse('2026-04-06 11:30:00');

        $session = Mockery::mock(StudySession::class)->makePartial();
        $session->shouldReceive('loadMissing')->with('technology')->andReturnSelf();
        $session->user_id = (string) Str::uuid();
        $session->id = $sessionId;
        $session->started_at = $startedAt;
        $session->ended_at = $endedAt;
        $session->duration_min = 90;
        $session->mood = 4;
        $session->focus_score = 8;

        $event = new SessionEnded($session);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('session', $payload);
        $sessionData = $payload['session'];
        $this->assertEquals($sessionId, $sessionData['id']);
        $this->assertEquals($startedAt->toIso8601String(), $sessionData['started_at']);
        $this->assertEquals($endedAt->toIso8601String(), $sessionData['ended_at']);
        $this->assertEquals(90, $sessionData['duration_min']);
        $this->assertArrayHasKey('duration_formatted', $sessionData);
        $this->assertEquals(4, $sessionData['mood']);
        $this->assertEquals(8, $sessionData['focus_score']);
    }
}
