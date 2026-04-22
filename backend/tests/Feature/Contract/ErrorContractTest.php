<?php

namespace Tests\Feature\Contract;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_422_error_has_code_and_message_and_details(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ],
            ]);

        $error = $response->json('error');
        $this->assertArrayHasKey('message', $error);
        $this->assertArrayHasKey('details', $error);
        $this->assertIsArray($error['details']);
    }

    public function test_401_error_structure(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                ],
            ]);
    }

    public function test_403_error_structure(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $tech = Technology::forceCreate([
            'user_id' => $userA->id,
            'name' => 'Private Tech',
            'slug' => 'private-tech',
            'color' => '#000000',
            'is_active' => true,
        ]);

        $tokenB = $userB->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->getJson("/api/v1/technologies/{$tech->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                ],
            ]);
    }

    public function test_404_error_structure(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/study-sessions/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                ],
            ]);
    }

    public function test_all_errors_have_success_false(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $tech = Technology::forceCreate([
            'user_id' => $userA->id,
            'name' => 'Owned Tech',
            'slug' => 'owned-tech',
            'color' => '#111111',
            'is_active' => true,
        ]);
        $tokenB = $userB->createToken('api-token')->plainTextToken;

        $r401 = $this->getJson('/api/v1/auth/me');
        $this->assertFalse($r401->json('success'), '401 should have success=false');

        $r403 = $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->getJson("/api/v1/technologies/{$tech->id}");
        $this->assertFalse($r403->json('success'), '403 should have success=false');

        $r404 = $this->withHeader('Authorization', 'Bearer '.$tokenB)
            ->getJson('/api/v1/study-sessions/00000000-0000-0000-0000-000000000000');
        $this->assertFalse($r404->json('success'), '404 should have success=false');

        $r422 = $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'bad',
        ]);
        $this->assertFalse($r422->json('success'), '422 should have success=false');
    }
}
