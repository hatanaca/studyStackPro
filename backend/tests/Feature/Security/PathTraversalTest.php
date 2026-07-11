<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PathTraversalTest extends TestCase
{
    use RefreshDatabase;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->token = $user->createToken('api-token')->plainTextToken;
    }

    public function test_path_traversal_in_technology_search(): void
    {
        $payloads = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd',
            '....//....//....//etc/passwd',
            '..%252f..%252f..%252fetc/passwd',
        ];

        foreach ($payloads as $payload) {
            $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
                ->getJson('/api/v1/technologies/search?q='.urlencode($payload));

            $response->assertStatus(200);
            $this->assertTrue($response->json('success'));
        }
    }

    public function test_path_traversal_in_session_notes(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => '00000000-0000-0000-0000-000000000000',
                'title' => '../../../etc/passwd',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
                'notes' => '../../etc/shadow',
            ]);

        $this->assertContains($response->getStatusCode(), [201, 422, 403, 404]);
    }

    public function test_path_traversal_in_date_filters(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/study-sessions?date_from=../../etc/passwd');

        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    public function test_null_bytes_in_input(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/technologies', [
                'name' => "test\x00.png",
                'color' => '#000000',
            ]);

        $this->assertContains($response->getStatusCode(), [201, 422]);
    }

    public function test_unicode_normalization_attack(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $payloads = [
            "\u002e\u002e\u002f", // ../
            "\u2025\u2025/", // ‥‥/
            "\u2024\u2024\u2024", // ․‥‥
        ];

        foreach ($payloads as $payload) {
            $response = $this->withHeader('Authorization', 'Bearer '.$token)
                ->getJson('/api/v1/technologies/search?q='.urlencode($payload));

            $response->assertStatus(200);
        }
    }
}
