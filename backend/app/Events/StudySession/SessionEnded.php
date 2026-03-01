<?php

namespace App\Events\StudySession;

use App\Models\StudySession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class SessionEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly StudySession $session,
    ) {
        $this->session->loadMissing('technology');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dashboard.'.$this->session->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return '.session.ended';
    }

    public function broadcastWith(): array
    {
        return [
            'session' => [
                'id' => $this->session->id,
                'started_at' => $this->session->started_at?->toIso8601String(),
                'ended_at' => $this->session->ended_at?->toIso8601String(),
                'duration_min' => $this->session->duration_min,
                'duration_formatted' => $this->session->duration_formatted,
                'mood' => $this->session->mood,
                'focus_score' => $this->session->focus_score,
            ],
        ];
    }
}
