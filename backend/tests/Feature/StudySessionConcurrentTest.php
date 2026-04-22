<?php

namespace Tests\Feature;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StudySessionConcurrentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
    }

    public function test_cannot_start_second_session_without_ending_first(): void
    {
        $user = User::factory()->create();
        $tech = Technology::forceCreate([
            'user_id' => $user->id,
            'name' => 'JavaScript',
            'slug' => 'javascript',
            'color' => '#F7DF1E',
            'is_active' => true,
        ]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $tech->id,
            ]);

        $response1->assertStatus(201)
            ->assertJson(['success' => true]);

        $response2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions/start', [
                'technology_id' => $tech->id,
            ]);

        $response2->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'CONCURRENT_SESSION',
                    'message' => 'O usuário já possui uma sessão ativa.',
                ],
            ]);
    }
}
