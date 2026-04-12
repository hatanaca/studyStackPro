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

class StudySessionStartEndTest extends TestCase
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
            'name' => 'Laravel',
            'slug' => 'laravel',
            'color' => '#FF2D20',
            'is_active' => true,
        ]);
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_start_creates_active_session(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $this->technology->id,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('study_sessions', [
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'ended_at' => null,
        ]);
    }

    public function test_start_without_technology_uses_first_user_tech(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions/start');

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('study_sessions', [
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'ended_at' => null,
        ]);
    }

    public function test_start_fails_when_session_already_active(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $this->technology->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $this->technology->id,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'CONCURRENT_SESSION',
                ],
            ]);
    }

    public function test_end_finalizes_session(): void
    {
        $startResponse = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $this->technology->id,
            ]);

        $sessionId = $startResponse->json('data.id');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/study-sessions/'.$sessionId.'/end');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $session = StudySession::find($sessionId);
        $this->assertNotNull($session->ended_at);
        $this->assertNotNull($session->duration_min);
    }

    public function test_end_fails_on_already_ended_session(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => Carbon::now()->subHour(),
            'ended_at' => Carbon::now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/study-sessions/'.$session->id.'/end');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'message' => 'Sessão já finalizada.',
                ],
            ]);
    }

    public function test_active_returns_session_with_elapsed_seconds(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $this->technology->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/active');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['elapsed_seconds']]);
    }

    public function test_active_returns_null_without_active_session(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/active');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => null,
            ]);
    }

    public function test_end_other_users_session_returns_403(): void
    {
        $otherUser = User::factory()->create();
        $otherTech = Technology::create([
            'user_id' => $otherUser->id,
            'name' => 'Python',
            'slug' => 'python',
            'color' => '#3776AB',
            'is_active' => true,
        ]);
        $session = StudySession::factory()->active()->create([
            'user_id' => $otherUser->id,
            'technology_id' => $otherTech->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/study-sessions/'.$session->id.'/end');

        $response->assertStatus(403);
    }

    public function test_start_after_ending_previous_session_succeeds(): void
    {
        $startResponse = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $this->technology->id,
            ]);

        $sessionId = $startResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/study-sessions/'.$sessionId.'/end');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $this->technology->id,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertEquals(
            2,
            StudySession::where('user_id', $this->user->id)->count()
        );
    }
}
