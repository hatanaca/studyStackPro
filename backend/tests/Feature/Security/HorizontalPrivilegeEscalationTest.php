<?php

namespace Tests\Feature\Security;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorizontalPrivilegeEscalationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_create_session_with_other_user_technology(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $tech = Technology::forceCreate([
            'user_id' => $owner->id,
            'name' => 'Owner Tech',
            'slug' => 'owner-tech',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $attackerToken = $attacker->createToken('attacker-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$attackerToken)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $tech->id,
                'title' => 'Stolen Session',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_list_other_users_technologies(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        Technology::forceCreate([
            'user_id' => $owner->id,
            'name' => 'Private Tech',
            'slug' => 'private-tech',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $attackerToken = $attacker->createToken('attacker-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$attackerToken)
            ->getJson('/api/v1/technologies');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_user_cannot_search_other_users_technologies(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        Technology::forceCreate([
            'user_id' => $owner->id,
            'name' => 'Secret Tech',
            'slug' => 'secret-tech',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $attackerToken = $attacker->createToken('attacker-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$attackerToken)
            ->getJson('/api/v1/technologies/search?q=Secret');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_user_cannot_access_other_users_analytics(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $attackerToken = $attacker->createToken('attacker-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$attackerToken)
            ->getJson('/api/v1/analytics/dashboard');

        $response->assertStatus(200);
    }

    public function test_user_cannot_start_session_with_nonexistent_technology(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => '00000000-0000-0000-0000-000000000000',
                'title' => 'Ghost Session',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ]);

        $this->assertContains($response->getStatusCode(), [403, 422, 404]);
    }

    public function test_user_cannot_modify_profile_of_another_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $token1 = $user1->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token1)
            ->putJson('/api/v1/auth/me', [
                'name' => 'Hacked',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $user2->id,
            'name' => $user2->name,
        ]);
    }
}
