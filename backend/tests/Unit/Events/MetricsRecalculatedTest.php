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
        $event = new MetricsRecalculated($userId);

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);

        $expected = new PrivateChannel('dashboard.'.$userId);
        $this->assertEquals($expected->name, $channels[0]->name);
    }

    public function test_broadcast_as_returns_correct_event_name(): void
    {
        $event = new MetricsRecalculated((string) Str::uuid());

        $this->assertEquals('.metrics.updated', $event->broadcastAs());
    }

    public function test_broadcast_with_returns_empty_payload(): void
    {
        $event = new MetricsRecalculated((string) Str::uuid());

        $payload = $event->broadcastWith();

        $this->assertIsArray($payload);
        $this->assertEmpty($payload);
    }
}
