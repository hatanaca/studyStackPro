<?php

namespace Tests\Feature\Analytics;

use App\Jobs\RecalculateMetricsJob;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Technology $technology;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
        $this->user = User::factory()->create();
        $this->technology = Technology::create([
            'user_id' => $this->user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_dashboard_returns_expected_structure(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/dashboard');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'user_metrics',
                    'technology_metrics',
                    'time_series_30d',
                    'top_technologies',
                ],
            ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/analytics/dashboard');

        $response->assertStatus(401);
    }

    public function test_user_metrics_returns_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/user-metrics');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_tech_stats_returns_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/tech-stats');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_time_series_returns_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/time-series?days=7');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_weekly_returns_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/weekly');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_heatmap_returns_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/heatmap');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_heatmap_accepts_year_parameter(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/heatmap?year=2025');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_recalculate_returns_202(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/analytics/recalculate');

        $response->assertStatus(202)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['job_id']]);
    }

    public function test_recalculate_dispatches_job(): void
    {
        Event::fake();
        Queue::fake([RecalculateMetricsJob::class]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/analytics/recalculate');

        $response->assertStatus(202);

        Queue::assertPushed(RecalculateMetricsJob::class, function (RecalculateMetricsJob $job) {
            return $job->userId === $this->user->id;
        });
    }
}
