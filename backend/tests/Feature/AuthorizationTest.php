<?php

namespace Tests\Feature;

use App\Models\StudySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
    }

    public function test_user_b_cannot_access_user_a_session(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $session = StudySession::create([
            'user_id' => $userA->id,
            'technology_id' => null,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
        ]);

        $token = $userB->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/study-sessions/{$session->id}");

        $response->assertStatus(403);
    }

    public function test_user_b_cannot_update_user_a_session(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $session = StudySession::create([
            'user_id' => $userA->id,
            'technology_id' => null,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
        ]);

        $token = $userB->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/study-sessions/{$session->id}", ['notes' => 'hack']);

        $response->assertStatus(403);
    }
}
