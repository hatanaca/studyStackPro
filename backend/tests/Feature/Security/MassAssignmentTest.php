<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_cannot_set_is_admin(): void
    {
        $response = $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
            ->postJson('/api/v1/auth/register', [
                'name' => 'Admin Attempt',
                'email' => 'admin@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_admin' => true,
                'is_superuser' => true,
            ]);

        $response->assertStatus(201);

        $user = User::where('email', 'admin@test.com')->first();
        $this->assertFalse((bool) ($user->is_admin ?? false));
        $this->assertFalse((bool) ($user->is_superuser ?? false));
    }

    public function test_profile_update_cannot_set_admin_fields(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/auth/me', [
                'name' => 'Hacked Name',
                'email' => $user->email,
                'is_admin' => true,
                'role' => 'admin',
            ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertArrayNotHasKey('is_admin', $user->getAttributes());
    }

    public function test_study_session_cannot_set_arbitrary_user_id(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;
        $otherUser = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => '00000000-0000-0000-0000-000000000000',
                'title' => 'Injection',
                'user_id' => $otherUser->id,
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ]);

        if ($response->getStatusCode() === 201) {
            $this->assertEquals($user->id, $response->json('data.user_id'));
        }
    }

    public function test_technology_cannot_set_arbitrary_user_id(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;
        $otherUser = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/technologies', [
                'name' => 'Injected Tech',
                'color' => '#000000',
                'user_id' => $otherUser->id,
            ]);

        $response->assertStatus(201);
        $this->assertEquals($user->id, $response->json('data.user_id'));
    }
}
