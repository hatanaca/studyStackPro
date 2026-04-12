<?php

namespace Tests\Feature\Contract;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserContractTest extends TestCase
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

    public function test_user_resource_contains_required_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $expectedKeys = ['id', 'name', 'email', 'timezone', 'locale', 'created_at', 'updated_at'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Missing key: {$key}");
        }

        $this->assertCount(count($expectedKeys), $data, 'UserResource has extra fields');
    }

    public function test_user_resource_does_not_expose_password(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('password', $response->json('data'));
    }

    public function test_user_resource_does_not_expose_remember_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('remember_token', $response->json('data'));
    }

    public function test_login_response_structure(): void
    {
        User::factory()->create([
            'email' => 'contract@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'contract@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                    'token_type',
                ],
            ]);
    }

    public function test_register_response_matches_login_response(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Contract Test',
            'email' => 'register-contract@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                    'token_type',
                ],
            ]);
    }
}
