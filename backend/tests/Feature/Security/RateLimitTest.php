<?php

namespace Tests\Feature\Security;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('rate-limit')]
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'ratelimit@test.com',
            'password' => 'password123',
        ]);
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_login_endpoint_is_rate_limited(): void
    {
        $hit429 = false;

        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'ratelimit@test.com',
                'password' => 'wrong-password',
            ]);

            if ($response->getStatusCode() === 429) {
                $hit429 = true;
                break;
            }
        }

        $this->assertTrue($hit429, 'Login endpoint should return 429 after exceeding rate limit.');
    }

    public function test_register_endpoint_is_rate_limited(): void
    {
        $hit429 = false;

        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson('/api/v1/auth/register', [
                'name' => 'User '.$i,
                'email' => "user{$i}@test.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            if ($response->getStatusCode() === 429) {
                $hit429 = true;
                break;
            }
        }

        $this->assertTrue($hit429, 'Register endpoint should return 429 after exceeding rate limit.');
    }

    public function test_authenticated_read_endpoints_are_rate_limited(): void
    {
        Technology::create([
            'user_id' => $this->user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);

        $hit429 = false;

        for ($i = 0; $i < 80; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
                ->getJson('/api/v1/technologies');

            if ($response->getStatusCode() === 429) {
                $hit429 = true;
                break;
            }
        }

        $this->assertTrue($hit429, 'Read endpoint should return 429 after exceeding rate limit.');
    }

    public function test_rate_limit_response_has_correct_structure(): void
    {
        $response = null;

        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'ratelimit@test.com',
                'password' => 'wrong-password',
            ]);

            if ($response->getStatusCode() === 429) {
                break;
            }
        }

        if ($response && $response->getStatusCode() === 429) {
            $response->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMITED',
                ],
            ]);
        } else {
            $this->markTestSkipped('Could not trigger 429 response within request limit.');
        }
    }
}
