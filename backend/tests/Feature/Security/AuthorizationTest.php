<?php

namespace Tests\Feature\Security;

use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use App\Modules\Analytics\Aggregators\MetricsAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Technology $techA;

    private Technology $techB;

    private string $tokenA;

    private string $tokenB;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();

        $this->userA = User::factory()->create();
        $this->userB = User::factory()->create();

        $this->techA = Technology::create([
            'user_id' => $this->userA->id,
            'name' => 'Laravel',
            'slug' => 'laravel',
            'color' => '#FF2D20',
            'is_active' => true,
        ]);

        $this->techB = Technology::create([
            'user_id' => $this->userB->id,
            'name' => 'Vue.js',
            'slug' => 'vuejs',
            'color' => '#42B883',
            'is_active' => true,
        ]);

        $this->tokenA = $this->userA->createToken('api-token')->plainTextToken;
        $this->tokenB = $this->userB->createToken('api-token')->plainTextToken;
    }

    public function test_user_b_cannot_view_user_a_session(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->userA->id,
            'technology_id' => $this->techA->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenB)
            ->getJson("/api/v1/study-sessions/{$session->id}");

        $response->assertStatus(403);
    }

    public function test_user_b_cannot_update_user_a_session(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->userA->id,
            'technology_id' => $this->techA->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenB)
            ->patchJson("/api/v1/study-sessions/{$session->id}", ['notes' => 'hack']);

        $response->assertStatus(403);
    }

    public function test_user_b_cannot_delete_user_a_session(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->userA->id,
            'technology_id' => $this->techA->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenB)
            ->deleteJson("/api/v1/study-sessions/{$session->id}");

        $response->assertStatus(403);
    }

    public function test_user_b_cannot_view_user_a_technology(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenB)
            ->getJson("/api/v1/technologies/{$this->techA->id}");

        $response->assertStatus(403);
    }

    public function test_user_b_cannot_update_user_a_technology(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenB)
            ->putJson("/api/v1/technologies/{$this->techA->id}", [
                'name' => 'Hacked',
                'color' => '#000000',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_b_cannot_delete_user_a_technology(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenB)
            ->deleteJson("/api/v1/technologies/{$this->techA->id}");

        $response->assertStatus(403);
    }

    public function test_user_b_cannot_create_session_with_user_a_technology(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenB)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->techA->id,
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus(403);
    }

    public function test_user_b_cannot_end_user_a_session(): void
    {
        $session = StudySession::factory()->active()->create([
            'user_id' => $this->userA->id,
            'technology_id' => $this->techA->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenB)
            ->patchJson("/api/v1/study-sessions/{$session->id}/end");

        $response->assertStatus(403);
    }

    public function test_user_a_dashboard_only_shows_own_data(): void
    {
        StudySession::factory()->create([
            'user_id' => $this->userA->id,
            'technology_id' => $this->techA->id,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
        ]);

        StudySession::factory()->create([
            'user_id' => $this->userB->id,
            'technology_id' => $this->techB->id,
            'started_at' => now()->subHours(3),
            'ended_at' => now()->subHours(2),
        ]);

        app(MetricsAggregator::class)->recalculateUserMetrics($this->userA->id, 'UTC');
        Cache::tags(['analytics', 'analytics:user:'.$this->userA->id])->flush();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->tokenA)
            ->getJson('/api/v1/analytics/dashboard');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $userMetrics = $response->json('data.user_metrics');
        $this->assertEquals(1, $userMetrics['total_sessions'] ?? $userMetrics['session_count'] ?? null);
    }

    public function test_unauthenticated_user_gets_401_on_protected_routes(): void
    {
        $endpoints = [
            ['GET', '/api/v1/auth/me'],
            ['GET', '/api/v1/study-sessions'],
            ['GET', '/api/v1/technologies'],
            ['GET', '/api/v1/analytics/dashboard'],
            ['POST', '/api/v1/study-sessions'],
            ['POST', '/api/v1/technologies'],
        ];

        foreach ($endpoints as [$method, $uri]) {
            $response = $this->json($method, $uri);
            $response->assertStatus(401, "Expected 401 for {$method} {$uri}");
        }
    }
}
