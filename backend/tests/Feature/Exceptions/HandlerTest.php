<?php

namespace Tests\Feature\Exceptions;

use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
    }

    public function test_validation_exception_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR'],
            ])
            ->assertJsonStructure([
                'error' => ['code', 'message', 'details'],
            ]);
    }

    public function test_authentication_exception_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'UNAUTHENTICATED'],
            ]);
    }

    public function test_authorization_exception_returns_403(): void
    {
        $otherUser = User::factory()->create();
        $tech = Technology::factory()->create(['user_id' => $otherUser->id]);

        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $tech->id,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    public function test_model_not_found_returns_404(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/study-sessions/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND'],
            ]);
    }

    public function test_concurrent_session_exception_returns_409(): void
    {
        $user = User::factory()->create();
        $tech = Technology::factory()->create(['user_id' => $user->id]);
        StudySession::factory()->create([
            'user_id' => $user->id,
            'technology_id' => $tech->id,
            'ended_at' => null,
        ]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $tech->id,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'CONCURRENT_SESSION'],
            ]);
    }

    public function test_error_response_structure_is_consistent(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'error' => ['code', 'message'],
            ]);

        $this->assertFalse($response->json('success'));
    }

    public function test_500_error_hides_details_in_production(): void
    {
        config(['app.debug' => false]);

        Route::middleware('api')->get('api/v1/test-500', function () {
            throw new \RuntimeException('Sensitive internal error details');
        });

        $response = $this->getJson('/api/v1/test-500');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Erro interno.',
                ],
            ]);

        $this->assertStringNotContainsString('Sensitive', $response->json('error.message'));
    }
}
