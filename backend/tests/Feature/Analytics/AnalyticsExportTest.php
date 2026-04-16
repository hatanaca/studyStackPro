<?php

namespace Tests\Feature\Analytics;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/analytics/export?start=2025-01-01&end=2025-01-31');

        $response->assertStatus(401);
    }

    public function test_export_returns_data_structure_for_valid_range(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/analytics/export?start=2025-01-01&end=2025-01-31');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'exported_at',
                    'period' => ['start', 'end'],
                    'data',
                ],
            ])
            ->assertJson(['success' => true]);
        $this->assertIsArray($response->json('data.data'));
    }

    public function test_export_validates_required_params(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/analytics/export');

        $response->assertStatus(422);
    }

    public function test_export_rejects_range_over_366_days(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/analytics/export?start=2024-01-01&end=2025-06-01');

        $response->assertStatus(422);
        $this->assertArrayHasKey('end', $response->json('error.details') ?? []);
    }
}
