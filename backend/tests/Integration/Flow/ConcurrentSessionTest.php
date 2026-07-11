<?php

namespace Tests\Integration\Flow;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConcurrentSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Technology $tech;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_only_one_active_session_per_user(): void
    {
        // Start first session
        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->tech->id,
                'title' => 'Session 1',
                'started_at' => now()->toIso8601String(),
            ])->assertStatus(201);

        // Check active session
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/active');

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data'));

        // End first session
        $sessionId = $response->json('data.id');
        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/study-sessions/'.$sessionId.'/end')
            ->assertStatus(200);

        // Start second session
        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->tech->id,
                'title' => 'Session 2',
                'started_at' => now()->toIso8601String(),
            ])->assertStatus(201);

        // Check active session again
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/active');

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data'));
        $this->assertEquals('Session 2', $response->json('data.title'));
    }

    public function test_session_duration_is_calculated_correctly(): void
    {
        $startedAt = now()->subMinutes(90);
        $endedAt = now()->subMinutes(30);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->tech->id,
                'title' => 'Timed Session',
                'started_at' => $startedAt->toIso8601String(),
                'ended_at' => $endedAt->toIso8601String(),
            ]);

        $response->assertStatus(201);
        $sessionId = $response->json('data.id');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/'.$sessionId);

        $response->assertStatus(200);
        $this->assertEquals(60, $response->json('data.duration_min'));
    }

    public function test_active_session_tracks_elapsed_seconds(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->tech->id,
                'title' => 'Active Session',
                'started_at' => now()->subMinutes(5)->toIso8601String(),
            ]);

        $response->assertStatus(201);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/active');

        $response->assertStatus(200);
        $this->assertArrayHasKey('elapsed_seconds', $response->json('data'));
        $this->assertGreaterThan(200, $response->json('data.elapsed_seconds'));
        $this->assertLessThan(400, $response->json('data.elapsed_seconds'));
    }

    public function test_session_with_all_optional_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->tech->id,
                'title' => 'Full Session',
                'started_at' => now()->subHours(2)->toIso8601String(),
                'ended_at' => now()->subHour()->toIso8601String(),
                'notes' => 'Learned about services and repositories',
                'mood' => 5,
                'focus_score' => 9,
            ]);

        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertEquals('Full Session', $data['title']);
        $this->assertEquals('Learned about services and repositories', $data['notes']);
        $this->assertEquals(5, $data['mood']);
        $this->assertEquals(9, $data['focus_score']);
    }

    public function test_session_list_isolation_between_users(): void
    {
        $otherUser = User::factory()->create();
        $otherTech = Technology::forceCreate([
            'user_id' => $otherUser->id,
            'name' => 'Other',
            'slug' => 'other',
            'color' => '#000000',
            'is_active' => true,
        ]);

        // Create sessions for both users
        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->tech->id,
                'title' => 'My Session',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ])->assertStatus(201);

        $otherToken = $otherUser->createToken('other-token')->plainTextToken;
        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $otherTech->id,
                'title' => 'Other Session',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ])->assertStatus(201);

        // Each user sees only their sessions
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions');

        $response->assertStatus(200);
        foreach ($response->json('data') as $session) {
            $this->assertEquals($this->user->id, $session['user_id']);
        }
    }
}
