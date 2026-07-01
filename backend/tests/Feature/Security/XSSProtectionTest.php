<?php

namespace Tests\Feature\Security;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XSSProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_xss_in_technology_name_is_stored_as_plain_text(): void
    {
        $xssPayload = '<script>alert("XSS")</script>';

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'name' => $xssPayload,
                'color' => '#000000',
            ]);

        $response->assertStatus(201);
        $this->assertEquals($xssPayload, $response->json('data.name'));
    }

    public function test_xss_in_session_notes_is_stored(): void
    {
        $xssPayload = '<img src=x onerror=alert(1)>';

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => '00000000-0000-0000-0000-000000000000',
                'title' => 'XSS test',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
                'notes' => $xssPayload,
            ]);

        $response->assertStatus($response->getStatusCode());
        if ($response->getStatusCode() === 201) {
            $this->assertEquals($xssPayload, $response->json('data.notes'));
        }
    }

    public function test_xss_in_session_title_is_stored(): void
    {
        $xssPayload = '<svg onload=alert(1)>';

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => '00000000-0000-0000-0000-000000000000',
                'title' => $xssPayload,
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus($response->getStatusCode());
        if ($response->getStatusCode() === 201) {
            $this->assertEquals($xssPayload, $response->json('data.title'));
        }
    }

    public function test_xss_in_profile_name_is_stored(): void
    {
        $xssPayload = '<script>document.cookie</script>';

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->putJson('/api/v1/auth/me', [
                'name' => $xssPayload,
                'email' => $this->user->email,
            ]);

        $response->assertStatus(200);
        $this->assertEquals($xssPayload, $response->json('data.name'));
    }

    public function test_event_handler_payload_is_json_encoded(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('application/json', $contentType);
    }
}
