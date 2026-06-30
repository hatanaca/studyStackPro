<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CSRFProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sanctum_csrf_cookie_endpoint_returns_204(): void
    {
        $response = $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
            ->getJson('/sanctum/csrf-cookie');

        $response->assertStatus(204);
        $response->assertCookie('XSRF-TOKEN');
    }

    public function test_stateful_api_requires_csrf_token_for_state_changing_operations(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_stateful_request_is_rejected(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://127.0.0.1:5173',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }
}
