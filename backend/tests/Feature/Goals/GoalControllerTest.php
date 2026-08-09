<?php

namespace Tests\Feature\Goals;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    public function test_index_lists_only_own_goals(): void
    {
        $mine = Goal::forceCreate([
            'user_id' => $this->user->id,
            'type' => 'minutes_per_week',
            'target_value' => 300,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        $other = Goal::forceCreate([
            'user_id' => User::factory()->create()->id,
            'type' => 'sessions_per_week',
            'target_value' => 5,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/v1/goals');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);
    }

    public function test_store_creates_goal(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/goals', [
                'type' => 'minutes_per_week',
                'target_value' => 420,
                'start_date' => now()->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['type' => 'minutes_per_week', 'target_value' => 420],
            ]);

        $this->assertDatabaseHas('goals', [
            'user_id' => $this->user->id,
            'type' => 'minutes_per_week',
            'target_value' => 420,
        ]);
    }

    public function test_store_rejects_invalid_type(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/goals', [
                'type' => 'invalid_type',
                'target_value' => 100,
                'start_date' => now()->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJson(['error' => ['code' => 'VALIDATION_ERROR']]);
    }

    public function test_show_returns_own_goal(): void
    {
        $goal = Goal::forceCreate([
            'user_id' => $this->user->id,
            'type' => 'streak_days',
            'target_value' => 30,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/goals/{$goal->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $goal->id);
    }

    public function test_show_cross_user_goal_is_forbidden(): void
    {
        $other = User::factory()->create();
        $goal = Goal::forceCreate([
            'user_id' => $other->id,
            'type' => 'streak_days',
            'target_value' => 30,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/goals/{$goal->id}");

        $response->assertStatus(403)
            ->assertJson(['error' => ['code' => 'FORBIDDEN']]);
    }

    public function test_update_goal(): void
    {
        $goal = Goal::forceCreate([
            'user_id' => $this->user->id,
            'type' => 'minutes_per_week',
            'target_value' => 300,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/goals/{$goal->id}", ['target_value' => 600]);

        $response->assertStatus(200)
            ->assertJsonPath('data.target_value', 600);
    }

    public function test_destroy_deletes_goal(): void
    {
        $goal = Goal::forceCreate([
            'user_id' => $this->user->id,
            'type' => 'sessions_per_week',
            'target_value' => 5,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/goals/{$goal->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
    }
}
