<?php

namespace Tests\Integration\Cache;

use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SessionCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Technology $tech;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
        $this->user = User::factory()->create();
        $this->tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Laravel',
            'slug' => 'laravel',
            'color' => '#FF2D20',
            'is_active' => true,
        ]);
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_creating_session_invalidates_analytics_cache(): void
    {
        $cacheKey = "dashboard:{$this->user->id}";
        Cache::tags(['analytics', "analytics:user:{$this->user->id}"])->put($cacheKey, ['cached' => true], 300);
        $this->assertTrue(Cache::tags(['analytics', "analytics:user:{$this->user->id}"])->has($cacheKey));

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->tech->id,
                'title' => 'Cache Test',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus(201);
    }

    public function test_deleting_session_invalidate_analytics_cache(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->tech->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/v1/study-sessions/'.$session->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('study_sessions', ['id' => $session->id]);
    }

    public function test_updating_session_invalidate_analytics_cache(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->tech->id,
            'notes' => 'Original',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/study-sessions/'.$session->id, [
                'notes' => 'Updated',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('study_sessions', [
            'id' => $session->id,
            'notes' => 'Updated',
        ]);
    }

    public function test_analytics_dashboard_returns_fresh_data_after_session_change(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/dashboard');

        $response->assertStatus(200);
        $this->assertArrayHasKey('user_metrics', $response->json('data'));
        $this->assertArrayHasKey('technology_metrics', $response->json('data'));
    }
}
