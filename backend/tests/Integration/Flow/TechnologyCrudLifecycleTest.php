<?php

namespace Tests\Integration\Flow;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TechnologyCrudLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_full_technology_lifecycle(): void
    {
        // Create
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'name' => 'Vue.js',
                'color' => '#42B883',
            ]);

        $response->assertStatus(201);
        $techId = $response->json('data.id');
        $this->assertDatabaseHas('technologies', ['id' => $techId, 'name' => 'Vue.js']);

        // Read
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies/'.$techId);

        $response->assertStatus(200);
        $this->assertEquals('Vue.js', $response->json('data.name'));

        // Update
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/v1/technologies/'.$techId, [
                'name' => 'Vue 3',
                'color' => '#42B883',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('Vue 3', $response->json('data.name'));

        // List
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));

        // Search
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/technologies/search?q=Vue');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));

        // Delete (soft)
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/v1/technologies/'.$techId);

        $response->assertStatus(200);
        $this->assertDatabaseHas('technologies', [
            'id' => $techId,
            'is_active' => false,
        ]);
    }

    public function test_technology_validation_rejects_invalid_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'name' => '',
                'color' => 'not-a-color',
            ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => ['code' => 'VALIDATION_ERROR']]);
        $this->assertArrayHasKey('name', $response->json('error.details') ?? []);
    }

    public function test_technology_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/technologies', [
            'name' => 'Vue.js',
            'color' => '#42B883',
        ]);

        $response->assertStatus(401);
    }

    public function test_technology_name_max_length(): void
    {
        $longName = str_repeat('A', 256);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'name' => $longName,
                'color' => '#000000',
            ]);

        $response->assertStatus(422);
    }

    public function test_deleting_technology_preserves_sessions(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'name' => 'Ruby',
                'color' => '#CC342D',
            ]);

        $techId = $response->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $techId,
                'title' => 'Ruby study',
                'started_at' => now()->subHours(2)->toIso8601String(),
                'ended_at' => now()->subHour()->toIso8601String(),
            ])->assertStatus(201);

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson('/api/v1/technologies/'.$techId)
            ->assertStatus(200);

        $this->assertDatabaseHas('study_sessions', [
            'technology_id' => $techId,
        ]);
    }
}
