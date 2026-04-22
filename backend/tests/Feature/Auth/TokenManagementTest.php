<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tokens_returns_list_of_user_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/tokens');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [['id', 'name', 'created_at', 'last_used_at']],
            ]);
    }

    public function test_tokens_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/tokens');

        $response->assertStatus(401);
    }

    public function test_revoke_all_tokens_removes_all(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/auth/tokens');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.revoked_count', 1);
    }

    public function test_revoke_all_tokens_invalidates_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/auth/tokens')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_tokens_shows_multiple_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('token-1');
        $user->createToken('token-2');
        $token3 = $user->createToken('token-3')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token3)
            ->getJson('/api/v1/auth/tokens');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_login_revokes_previous_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);
        $oldToken = $user->createToken('api-token')->plainTextToken;

        $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ])->assertStatus(200);

        $this->assertSame(0, $user->fresh()->tokens()->count(), 'Login deve revogar PATs existentes.');

        // Evita que a sessão web do login autentique o pedido seguinte (Sanctum aceita sessão ou PAT).
        $this->flushSession();

        $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }
}
