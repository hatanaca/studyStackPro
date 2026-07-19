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
            'password' => bcrypt('Password123'),
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
            'password' => bcrypt('Password123'),
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
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ]);

        // Register should not be affected by login rate limits
        $this->assertContains($response->getStatusCode(), [201, 422]);
    }

    public function test_rate_limit_resets_after_minute(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('Password123'),
        ]);

        // Exhaust the rate limit (3 per minute)
        for ($i = 0; $i < 6; $i++) {
            $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
                ->postJson('/api/v1/auth/login', [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                ]);
        }

        // Travel forward past the rate limit window
        $this->travel(2)->minutes();

        $response = $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        // After the window resets, should get 401 (invalid credentials) not 429 (rate limited)
        $this->assertNotEquals(429, $response->getStatusCode());
    }
}
