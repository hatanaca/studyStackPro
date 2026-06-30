<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    private function spaHeaders(): array
    {
        return ['Origin' => 'http://127.0.0.1:5173'];
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/register', [
            'name' => 'Duplicate',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_rejects_mismatched_passwords(): void
    {
        $response = $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/register', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_rejects_missing_fields(): void
    {
        $response = $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422);
    }

    public function test_login_rejects_nonexistent_email(): void
    {
        $response = $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_profile_updates_name(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/auth/me', [
                'name' => 'New Name',
                'email' => $user->email,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_update_profile_rejects_invalid_email(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/auth/me', [
                'name' => $user->name,
                'email' => 'not-an-email',
            ]);

        $response->assertStatus(422);
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'wrong',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422);
    }

    public function test_change_password_with_correct_password_succeeds(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'password123',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ]);

        $response->assertStatus(200);
    }

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }

    public function test_tokens_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/tokens');
        $response->assertStatus(401);
    }

    public function test_revoke_tokens_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/v1/auth/tokens');
        $response->assertStatus(401);
    }

    public function test_register_returns_user_with_expected_fields(): void
    {
        $response = $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/register', [
            'name' => 'Full Test',
            'email' => 'full@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'timezone'],
                ],
            ]);

        $this->assertEquals('America/Sao_Paulo', $response->json('data.user.timezone'));
    }
}
