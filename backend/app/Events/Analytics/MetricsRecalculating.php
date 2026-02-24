<?php

namespace App\Events\Analytics;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class MetricsRecalculating implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly string $userId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dashboard.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'metrics.recalculating';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
