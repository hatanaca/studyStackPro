<?php

namespace App\Events\StudySession;

use App\Models\StudySession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class SessionStarted implements ShouldBroadcast
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
        return '.session.started';
    }

    public function broadcastWith(): array
    {
        $elapsedSeconds = (int) $this->session->started_at->diffInSeconds(now());

        return [
            'session' => [
                'id' => $this->session->id,
                'technology' => $this->session->technology ? [
                    'id' => $this->session->technology->id,
                    'name' => $this->session->technology->name,
                    'color' => $this->session->technology->color,
                    'slug' => $this->session->technology->slug,
                ] : null,
                'started_at' => $this->session->started_at?->toIso8601String(),
                'ended_at' => null,
                'elapsed_seconds' => $elapsedSeconds,
            ],
        ];
    }
}
