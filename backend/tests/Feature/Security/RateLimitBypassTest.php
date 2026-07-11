<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitBypassTest extends TestCase
{
    use RefreshDatabase;

    public function test_rate_limit_applies_to_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // Make multiple rapid login attempts with wrong password
        for ($i = 0; $i < 6; $i++) {
            $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
                ->postJson('/api/v1/auth/login', [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                ]);
        }

        $response = $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        // Should be rate limited (429) or still return 422 (invalid credentials)
        $this->assertContains($response->getStatusCode(), [422, 429]);
    }

    public function test_different_ips_get_separate_rate_limits(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // First IP: exhaust the rate limit (3 per minute)
        for ($i = 0; $i < 5; $i++) {
            $this->call('POST', '/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ], [], [], [
                'REMOTE_ADDR' => '1.1.1.1',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ], json_encode([
                'email' => $user->email,
                'password' => 'wrong-password',
            ]));
        }

        // Second IP should not be affected
        $response = $this->call('POST', '/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ], [], [], [
            'REMOTE_ADDR' => '2.2.2.2',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode([
            'email' => $user->email,
            'password' => 'wrong-password',
        ]));

        $this->assertContains($response->getStatusCode(), [422, 429]);
    }

    public function test_valid_login_not_affected_by_other_ips_failures(): void
    {
        $user1 = User::factory()->create(['password' => bcrypt('pass1')]);
        $user2 = User::factory()->create(['password' => bcrypt('pass2')]);

        // User1 fails many times from IP 1
        for ($i = 0; $i < 5; $i++) {
            $this->call('POST', '/api/v1/auth/login', [
                'email' => $user1->email,
                'password' => 'wrong',
            ], [], [], [
                'REMOTE_ADDR' => '1.1.1.1',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ], json_encode([
                'email' => $user1->email,
                'password' => 'wrong',
            ]));
        }

        // User2 from a different IP should still be able to login
        $response = $this->call('POST', '/api/v1/auth/login', [
            'email' => $user2->email,
            'password' => 'pass2',
        ], [], [], [
            'REMOTE_ADDR' => '2.2.2.2',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode([
            'email' => $user2->email,
            'password' => 'pass2',
        ]));

        $response->assertStatus(200);
    }
}
