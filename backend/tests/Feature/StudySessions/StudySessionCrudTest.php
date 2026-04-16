<?php

namespace Tests\Feature\StudySessions;

use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StudySessionCrudTest extends TestCase
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
            'name' => 'Vue.js',
            'slug' => 'vuejs',
            'color' => '#42B883',
            'is_active' => true,
        ]);
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_index_returns_sessions_for_authenticated_user(): void
    {
        StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_index_filters_by_technology_id(): void
    {
        $tech2 = Technology::create([
            'user_id' => $this->user->id,
            'name' => 'Laravel',
            'slug' => 'laravel',
            'color' => '#FF2D20',
            'is_active' => true,
        ]);
        StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);
        StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $tech2->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions?technology_id='.$this->technology->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->technology->id, $data[0]['technology_id']);
    }

    public function test_store_creates_session(): void
    {
        $startedAt = Carbon::now()->subHours(2);
        $endedAt = Carbon::now()->subHour();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->technology->id,
                'started_at' => $startedAt->toIso8601String(),
                'ended_at' => $endedAt->toIso8601String(),
                'notes' => 'Sessão de estudo',
                'mood' => 4,
                'focus_score' => 8,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Sessão criada.',
                'data' => [
                    'technology_id' => $this->technology->id,
                    'notes' => 'Sessão de estudo',
                    'mood' => 4,
                    'focus_score' => 8,
                ],
            ]);

        $this->assertDatabaseHas('study_sessions', [
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'notes' => 'Sessão de estudo',
        ]);
    }

    public function test_store_rejects_technology_from_another_user(): void
    {
        $otherUser = User::factory()->create();
        $otherTech = Technology::create([
            'user_id' => $otherUser->id,
            'name' => 'Outro',
            'slug' => 'outro',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $otherTech->id,
                'started_at' => Carbon::now()->toIso8601String(),
            ]);

        $response->assertStatus(403);
    }

    public function test_show_returns_session_for_owner(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/'.$session->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $session->id, 'technology_id' => $this->technology->id],
            ]);
    }

    public function test_show_returns_403_for_cross_user(): void
    {
        $otherUser = User::factory()->create();
        $otherTech = Technology::create([
            'user_id' => $otherUser->id,
            'name' => 'Outro',
            'slug' => 'outro',
            'color' => '#000000',
            'is_active' => true,
        ]);
        $session = StudySession::factory()->create([
            'user_id' => $otherUser->id,
            'technology_id' => $otherTech->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/'.$session->id);

        $response->assertStatus(403);
    }

    public function test_show_returns_404_for_invalid_id(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }

    public function test_update_modifies_session(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'notes' => 'Original',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/study-sessions/'.$session->id, [
                'notes' => 'Notas atualizadas',
                'mood' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['notes' => 'Notas atualizadas', 'mood' => 5],
            ]);

        $session->refresh();
        $this->assertEquals('Notas atualizadas', $session->notes);
        $this->assertEquals(5, $session->mood);
    }

    public function test_destroy_deletes_session(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/v1/study-sessions/'.$session->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('study_sessions', ['id' => $session->id]);
    }

    public function test_end_finalizes_active_session(): void
    {
        $session = StudySession::factory()->active()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/study-sessions/'.$session->id.'/end');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $session->refresh();
        $this->assertNotNull($session->ended_at);
        $this->assertNotNull($session->duration_min);
    }
}
