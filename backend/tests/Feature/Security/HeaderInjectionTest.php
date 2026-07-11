<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderInjectionTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->token = $user->createToken('api-token')->plainTextToken;
    }

    public function test_crlf_injection_in_authorization_header(): void
    {
        $response = $this->withHeader('Authorization', "Bearer test\r\nX-Injected: true")
            ->getJson('/api/v1/auth/me');

        $this->assertContains($response->getStatusCode(), [401, 400]);
    }

    public function test_host_header_injection(): void
    {
        $response = $this->withHeader('Host', 'evil.com')
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
    }

    public function test_x_forwarded_for_spoofing(): void
    {
        $response = $this->withHeader('X-Forwarded-For', '127.0.0.1')
            ->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
    }

    public function test_custom_injected_headers_ignored(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->withHeader('X-Admin', 'true')
            ->withHeader('X-Real-IP', '10.0.0.1')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
    }

    public function test_oversized_authorization_header(): void
    {
        $longToken = str_repeat('A', 10000);

        $response = $this->withHeader('Authorization', 'Bearer '.$longToken)
            ->getJson('/api/v1/auth/me');

        $this->assertContains($response->getStatusCode(), [401, 400, 431]);
    }
}
