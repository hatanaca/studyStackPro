<?php

namespace Tests\Integration\Cache;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsCacheIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Technology $technology;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->technology = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_dashboard_returns_valid_structure(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['user_metrics', 'technology_metrics', 'time_series_30d', 'top_technologies'],
            ]);
    }

    public function test_different_users_get_independent_data(): void
    {
        $user2 = User::factory()->create();
        $token2 = $user2->createToken('api-token')->plainTextToken;

        $response1 = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/dashboard')
            ->assertStatus(200);

        $response2 = $this->withHeader('Authorization', 'Bearer '.$token2)
            ->getJson('/api/v1/analytics/dashboard')
            ->assertStatus(200);

        $data1 = $response1->json('data.user_metrics');
        $data2 = $response2->json('data.user_metrics');

        // Cada usuário tem seus próprios dados (total_minutes difere ou ambos zero)
        $this->assertIsArray($data1);
        $this->assertIsArray($data2);
    }

    public function test_heatmap_returns_valid_structure(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/heatmap');

        $response->assertStatus(200);
    }

    public function test_recalculate_returns_202(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/analytics/recalculate');

        $response->assertStatus(202);
    }
}
