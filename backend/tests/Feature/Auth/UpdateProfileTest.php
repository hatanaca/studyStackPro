<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_profile_changes_name(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/auth/me', ['name' => 'New Name']);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['name' => 'New Name'],
            ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_update_profile_changes_email(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/auth/me', ['email' => 'new@example.com']);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['email' => 'new@example.com'],
            ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'new@example.com']);
    }

    public function test_update_profile_changes_timezone(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/auth/me', ['timezone' => 'America/Sao_Paulo']);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'timezone' => 'America/Sao_Paulo']);
    }

    public function test_update_profile_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/auth/me', ['email' => 'taken@example.com']);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR'],
            ]);
    }

    public function test_update_profile_requires_authentication(): void
    {
        $response = $this->putJson('/api/v1/auth/me', ['name' => 'Test']);

        $response->assertStatus(401);
    }

    public function test_update_profile_ignores_password_field(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'original-password',
        ]);
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/auth/me', [
                'name' => 'Updated',
                'password' => 'hacked-password',
            ])
            ->assertStatus(200);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'original-password',
        ])->assertStatus(200);
    }
}
