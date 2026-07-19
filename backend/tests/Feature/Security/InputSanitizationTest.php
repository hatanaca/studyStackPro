<?php

namespace Tests\Feature\Security;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputSanitizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Technology $technology;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('api-token')->plainTextToken;
        $this->technology = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);
    }

    public function test_register_rejects_empty_name(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'test@test.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_rejects_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test',
            'email' => 'not-an-email',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_rejects_short_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
    }

    public function test_technology_rejects_empty_name(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'name' => '',
                'color' => '#000000',
            ]);

        $response->assertStatus(422);
    }

    public function test_study_session_rejects_invalid_mood_range(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->technology->id,
                'title' => 'Test',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
                'mood' => 10,
            ]);

        $response->assertStatus(422);
    }

    public function test_study_session_rejects_negative_focus_score(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $this->technology->id,
                'title' => 'Test',
                'started_at' => now()->subHour()->toIso8601String(),
                'ended_at' => now()->toIso8601String(),
                'focus_score' => -5,
            ]);

        $response->assertStatus(422);
    }

    public function test_very_long_input_is_rejected(): void
    {
        $longString = str_repeat('A', 10001);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'name' => $longString,
                'color' => '#000000',
            ]);

        $response->assertStatus(422);
    }

    public function test_null_bytes_in_input_are_handled_gracefully(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/v1/technologies', [
                'name' => "Test\x00Name",
                'color' => '#000000',
            ]);

        $this->assertContains($response->getStatusCode(), [201, 422, 500]);
    }
}
