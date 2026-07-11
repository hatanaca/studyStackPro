<?php

namespace Tests\Feature\Security;

use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IDORTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $attacker;
    private string $userToken;
    private string $attackerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->attacker = User::factory()->create();
        $this->userToken = $this->user->createToken('user-token')->plainTextToken;
        $this->attackerToken = $this->attacker->createToken('attacker-token')->plainTextToken;
    }

    public function test_attacker_cannot_access_user_technology(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Private Tech',
            'slug' => 'private-tech',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->attackerToken)
            ->getJson('/api/v1/technologies/'.$tech->id);

        $response->assertStatus(403);
    }

    public function test_attacker_cannot_access_user_session(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Tech',
            'slug' => 'tech',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $session = StudySession::forceCreate([
            'user_id' => $this->user->id,
            'technology_id' => $tech->id,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->attackerToken)
            ->getJson('/api/v1/study-sessions/'.$session->id);

        $response->assertStatus(403);
    }

    public function test_attacker_cannot_update_user_session(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Tech',
            'slug' => 'tech',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $session = StudySession::forceCreate([
            'user_id' => $this->user->id,
            'technology_id' => $tech->id,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'notes' => 'Original',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->attackerToken)
            ->patchJson('/api/v1/study-sessions/'.$session->id, [
                'notes' => 'Hacked',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('study_sessions', [
            'id' => $session->id,
            'notes' => 'Original',
        ]);
    }

    public function test_attacker_cannot_delete_user_session(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Tech',
            'slug' => 'tech',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $session = StudySession::forceCreate([
            'user_id' => $this->user->id,
            'technology_id' => $tech->id,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->attackerToken)
            ->deleteJson('/api/v1/study-sessions/'.$session->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('study_sessions', ['id' => $session->id]);
    }

    public function test_attacker_cannot_update_user_technology(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Original',
            'slug' => 'original',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->attackerToken)
            ->putJson('/api/v1/technologies/'.$tech->id, [
                'name' => 'Hacked',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('technologies', [
            'id' => $tech->id,
            'name' => 'Original',
        ]);
    }

    public function test_attacker_cannot_delete_user_technology(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Tech',
            'slug' => 'tech',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->attackerToken)
            ->deleteJson('/api/v1/technologies/'.$tech->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('technologies', [
            'id' => $tech->id,
            'is_active' => true,
        ]);
    }

    public function test_user_cannot_see_other_users_sessions_in_list(): void
    {
        $tech = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'Tech',
            'slug' => 'tech',
            'color' => '#000000',
            'is_active' => true,
        ]);

        StudySession::forceCreate([
            'user_id' => $this->user->id,
            'technology_id' => $tech->id,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->attackerToken)
            ->getJson('/api/v1/study-sessions');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }
}
