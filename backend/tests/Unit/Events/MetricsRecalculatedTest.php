<?php

namespace Tests\Unit\Events;

use App\Events\Analytics\MetricsRecalculated;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Str;
use Tests\TestCase;

class MetricsRecalculatedTest extends TestCase
{
    public function test_broadcasts_on_correct_channel(): void
    {
        $userId = (string) Str::uuid();
        $event = new MetricsRecalculated($userId, ['total_hours' => 100]);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);

        $expected = new PrivateChannel('dashboard.'.$userId);
        $this->assertEquals($expected->name, $channels[0]->name);
    }

    public function test_broadcast_as_returns_correct_event_name(): void
    {
        $event = new MetricsRecalculated((string) Str::uuid(), []);

        $this->assertEquals('.metrics.updated', $event->broadcastAs());
    }

    public function test_broadcast_with_contains_dashboard_data(): void
    {
        $dashboardData = ['total_hours' => 100, 'streak' => 5];
        $event = new MetricsRecalculated((string) Str::uuid(), $dashboardData);

        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('dashboard', $payload);
        $this->assertEquals($dashboardData, $payload['dashboard']);
    }
}
