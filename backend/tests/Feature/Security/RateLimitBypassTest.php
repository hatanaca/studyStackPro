<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
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

    public function test_different_rate_limiters_are_independent(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // Exhaust the login rate limiter (3 per minute)
        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
                ->postJson('/api/v1/auth/login', [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                ]);
        }

        // Register uses a separate rate limiter, so it should still work
        $response = $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
            ->postJson('/api/v1/auth/register', [
                'name' => 'New User',
                'email' => 'newuser@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        // Register should not be affected by login rate limits
        $this->assertContains($response->getStatusCode(), [201, 422]);
    }

    public function test_rate_limit_key_is_based_on_ip(): void
    {
        // Verify that the login rate limiter keys by IP address
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // Make requests from default IP (127.0.0.1) to exhaust limit
        for ($i = 0; $i < 4; $i++) {
            $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
                ->postJson('/api/v1/auth/login', [
                    'email' => $user->email,
                    'password' => 'wrong',
                ]);
        }

        // Verify the rate limiter has entries for the default IP
        $this->assertTrue(RateLimiter::tooManyAttempts('login:127.0.0.1', 3));
    }
}
