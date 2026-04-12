<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'old-password',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Senha alterada. Reconecte seus dispositivos.',
            ]);
    }

    public function test_change_password_with_incorrect_current_password(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Senha atual incorreta.',
                ],
            ]);
    }

    public function test_change_password_revokes_all_tokens(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'old-password',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ])
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_change_password_requires_confirmation(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'password',
                'password' => 'new-password123',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR'],
            ]);
    }

    public function test_change_password_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(401);
    }
}
