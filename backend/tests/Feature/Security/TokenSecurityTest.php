<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_token_returns_401(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token', [], now()->subHour())->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_invalid_token_format_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-format')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_empty_bearer_token_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_revoked_token_returns_401(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $user->tokens()->delete();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_token_from_other_user_cannot_access_protected_route(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $tokenB = $userB->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $this->assertEquals($userB->id, $response->json('data.id'));
    }

    public function test_malformed_authorization_header_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Basic dXNlcjpwYXNz')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_no_authorization_header_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}
