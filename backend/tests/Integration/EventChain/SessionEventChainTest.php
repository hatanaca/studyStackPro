<?php

namespace Tests\Integration\EventChain;

use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SessionEventChainTest extends TestCase
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

    public function test_session_creation_dispatches_created_event(): void
    {
        Event::fake([StudySessionCreated::class]);

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->technology->id,
                'title' => 'Event test',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ]);

        Event::assertDispatched(StudySessionCreated::class, function ($event) {
            return $event->session->user_id === $this->user->id;
        });
    }

    public function test_session_update_dispatches_updated_event(): void
    {
        Event::fake([StudySessionUpdated::class]);

        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->patchJson('/api/v1/study-sessions/'.$session->id, [
                'notes' => 'Updated notes',
            ]);

        Event::assertDispatched(StudySessionUpdated::class);
    }

    public function test_session_deletion_dispatches_deleted_event(): void
    {
        Event::fake([StudySessionDeleted::class]);

        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/v1/study-sessions/'.$session->id);

        Event::assertDispatched(StudySessionDeleted::class);
    }

    public function test_concurrent_sessions_are_prevented_by_database_trigger(): void
    {
        StudySession::forceCreate([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->subMinutes(30),
            'ended_at' => null,
            'notes' => null,
            'mood' => null,
            'focus_score' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $this->technology->id,
            ]);

        $response->assertStatus(409)
            ->assertJson(['success' => false]);
    }

    public function test_duration_is_auto_calculated_by_trigger(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->technology->id,
                'title' => 'Duration test',
                'started_at' => now()->subHours(3)->toIso8601String(),
                'ended_at' => now()->subHours(2)->toIso8601String(),
            ]);

        $response->assertStatus(201);

        $sessionId = $response->json('data.id');
        $this->assertDatabaseHas('study_sessions', [
            'id' => $sessionId,
        ]);

        $sessionInDb = StudySession::find($sessionId);
        $this->assertNotNull($sessionInDb->duration_min);
        $this->assertGreaterThan(0, $sessionInDb->duration_min);
    }
}
