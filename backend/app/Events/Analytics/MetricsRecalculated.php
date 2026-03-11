<?php

namespace App\Events\Analytics;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Evento broadcast quando recálculo de métricas termina.
 * Canal privado dashboard.{userId}. Payload: dashboard (objeto completo).
 */
class MetricsRecalculated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly string $userId,
        public readonly array $dashboardData,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dashboard.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return '.metrics.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'dashboard' => $this->dashboardData,
        ];
    }
}
