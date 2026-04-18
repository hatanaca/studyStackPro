<?php

namespace Tests\Feature\Contract;

use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StudySessionContractTest extends TestCase
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

    public function test_session_resource_contains_required_fields(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson("/api/v1/study-sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $requiredKeys = [
            'id', 'user_id', 'technology_id', 'title', 'started_at', 'ended_at',
            'duration_min', 'notes', 'mood', 'focus_score', 'productivity_score',
            'duration_formatted', 'created_at', 'updated_at',
        ];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Missing key: {$key}");
        }
    }

    public function test_session_list_response_has_pagination_meta(): void
    {
        StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_session_resource_includes_technology_when_loaded(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->technology->id,
                'title' => 'Sessão contrato',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $technology = $response->json('data.technology');
        $this->assertNotNull($technology);
        $this->assertArrayHasKey('id', $technology);
        $this->assertArrayHasKey('name', $technology);
        $this->assertArrayHasKey('slug', $technology);
        $this->assertArrayHasKey('color', $technology);
    }

    public function test_active_session_includes_elapsed_seconds(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $this->technology->id,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/study-sessions/active');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $elapsedSeconds = $response->json('data.elapsed_seconds');
        $this->assertIsInt($elapsedSeconds);
    }

    public function test_dates_are_iso8601_format(): void
    {
        $session = StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson("/api/v1/study-sessions/{$session->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $iso8601Pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/';

        $this->assertMatchesRegularExpression($iso8601Pattern, $data['started_at']);
        $this->assertMatchesRegularExpression($iso8601Pattern, $data['created_at']);
    }
}
