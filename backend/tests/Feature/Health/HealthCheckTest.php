<?php

namespace Tests\Feature\Health;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\Concerns\InteractsWithRealRedis;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use InteractsWithRealRedis;
    use RefreshDatabase;

    public function test_health_returns_200_when_all_services_ok(): void
    {
        $this->skipUnlessRedisPingReachable();

        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'version' => '1.0.0',
            ])
            ->assertJsonStructure(['status', 'version', 'timestamp']);
    }

    public function test_health_includes_services_in_testing_environment(): void
    {
        $this->skipUnlessRedisPingReachable();

        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'services' => ['database', 'redis', 'queue', 'websocket'],
            ]);
    }

    public function test_health_response_structure(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertJsonStructure(['status', 'version', 'timestamp']);
    }

    public function test_health_returns_503_when_database_unavailable(): void
    {
        DB::shouldReceive('connection')
            ->once()
            ->andThrow(new \Exception('Connection refused'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson(['status' => 'degraded']);
    }

    public function test_health_returns_503_when_redis_unavailable(): void
    {
        Redis::shouldReceive('ping')
            ->once()
            ->andThrow(new \Exception('Connection refused'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson(['status' => 'degraded']);
    }

    public function test_health_does_not_require_authentication(): void
    {
        $this->skipUnlessRedisPingReachable();

        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $this->assertNotEquals(401, $response->getStatusCode());
    }
}
