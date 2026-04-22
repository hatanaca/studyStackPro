<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** Origem “SPA” para activar middleware stateful do Sanctum nos testes. */
    private function spaHeaders(): array
    {
        return ['Origin' => 'http://127.0.0.1:5173'];
    }

    public function test_register_creates_user_and_returns_user_without_token(): void
    {
        $response = $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registrado com sucesso.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'timezone', 'locale', 'created_at', 'updated_at'],
                ],
            ]);

        $this->assertArrayNotHasKey('token', $response->json('data') ?? []);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    public function test_login_returns_user_without_token_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response = $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                ],
            ]);

        $this->assertArrayNotHasKey('token', $response->json('data') ?? []);
    }

    public function test_login_returns_401_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $response = $this->withHeaders($this->spaHeaders())->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Credenciais inválidas.',
                ],
            ]);
    }

    public function test_logout_revokes_bearer_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Sessão terminada.',
            ]);

        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);
    }
}
