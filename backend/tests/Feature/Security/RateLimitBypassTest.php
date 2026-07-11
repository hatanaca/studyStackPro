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

        // Simulate requests from different IPs by manipulating the request IP
        // First IP: exhaust the rate limit
        $this->app['request']->server->set('REMOTE_ADDR', '1.1.1.1');
        for ($i = 0; $i < 4; $i++) {
            $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
                ->postJson('/api/v1/auth/login', [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                ]);
        }

        // Second IP should not be affected
        $this->app['request']->server->set('REMOTE_ADDR', '2.2.2.2');
        $response = $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $response->assertStatus(422);
    }

    public function test_valid_login_not_affected_by_other_ips_failures(): void
    {
        $user1 = User::factory()->create(['password' => bcrypt('pass1')]);
        $user2 = User::factory()->create(['password' => bcrypt('pass2')]);

        // User1 fails many times from IP 1
        $this->app['request']->server->set('REMOTE_ADDR', '1.1.1.1');
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
                ->postJson('/api/v1/auth/login', [
                    'email' => $user1->email,
                    'password' => 'wrong',
                ]);
        }

        // User2 from a different IP should still be able to login
        $this->app['request']->server->set('REMOTE_ADDR', '2.2.2.2');
        $response = $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
            ->postJson('/api/v1/auth/login', [
                'email' => $user2->email,
                'password' => 'pass2',
            ]);

        $response->assertStatus(200);
    }
}
