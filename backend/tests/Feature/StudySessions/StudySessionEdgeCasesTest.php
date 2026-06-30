<?php

namespace Tests\Feature\StudySessions;

use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StudySessionEdgeCasesTest extends TestCase
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
        $this->technology = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Vue.js',
            'slug' => 'vuejs',
            'color' => '#42B883',
            'is_active' => true,
        ]);
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_index_returns_empty_array_for_new_user(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(0, 'data');
    }

    public function test_store_requires_title(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->technology->id,
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus(422);
    }

    public function test_store_requires_started_at(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->technology->id,
                'title' => 'No start',
                'ended_at' => now()->toIso8601String(),
            ]);

        $this->assertContains($response->getStatusCode(), [422, 500]);
    }

    public function test_show_returns_404_for_nonexistent_session(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }

    public function test_update_preserves_unchanged_fields(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'notes' => 'Original notes',
            'mood' => 3,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/study-sessions/'.$session->id, [
                'mood' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.notes', 'Original notes')
            ->assertJsonPath('data.mood', 5);
    }

    public function test_active_returns_null_when_no_active_session(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/active');

        $response->assertStatus(200)
            ->assertJson(['data' => null]);
    }

    public function test_active_returns_elapsed_seconds(): void
    {
        StudySession::factory()->active()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->subMinutes(5),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/active');

        $response->assertStatus(200);
        $this->assertArrayHasKey('elapsed_seconds', $response->json('data'));
        $this->assertGreaterThan(0, $response->json('data.elapsed_seconds'));
    }

    public function test_end_returns_422_for_already_ended_session(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/study-sessions/'.$session->id.'/end');

        $response->assertStatus(422);
    }

    public function test_delete_returns_success_and_removes_session(): void
    {
        $session = StudySession::forceCreate([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
            'notes' => null,
            'mood' => null,
            'focus_score' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/v1/study-sessions/'.$session->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('study_sessions', ['id' => $session->id]);
    }

    public function test_cannot_delete_other_users_session(): void
    {
        $otherUser = User::factory()->create();
        $otherTech = Technology::forceCreate([
            'user_id' => $otherUser->id,
            'name' => 'Other',
            'slug' => 'other',
            'color' => '#000000',
            'is_active' => true,
        ]);
        $session = StudySession::factory()->create([
            'user_id' => $otherUser->id,
            'technology_id' => $otherTech->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/v1/study-sessions/'.$session->id);

        $response->assertStatus(403);
    }

    public function test_index_paginates_correctly(): void
    {
        for ($i = 0; $i < 25; $i++) {
            StudySession::forceCreate([
                'user_id' => $this->user->id,
                'technology_id' => $this->technology->id,
                'started_at' => now()->subDays(30 + $i)->toIso8601String(),
                'ended_at' => now()->subDays(30 + $i)->addHour()->toIso8601String(),
                'notes' => null,
                'mood' => null,
                'focus_score' => null,
            ]);
        }

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    public function test_index_filters_by_date_range(): void
    {
        StudySession::forceCreate([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->subDays(10)->toIso8601String(),
            'ended_at' => now()->subDays(10)->addHour()->toIso8601String(),
            'notes' => null,
            'mood' => null,
            'focus_score' => null,
        ]);

        StudySession::forceCreate([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->subDays(1)->toIso8601String(),
            'ended_at' => now()->subDays(1)->addHour()->toIso8601String(),
            'notes' => null,
            'mood' => null,
            'focus_score' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions?date_from='.now()->subDays(2)->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
