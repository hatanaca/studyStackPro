<?php

namespace Tests\Integration\FullFlow;

use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use App\Modules\Analytics\Aggregators\MetricsAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UserRegistrationToStudySessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
    }

    public function test_full_flow_register_create_tech_create_session_verify_analytics(): void
    {
        $response = $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
            ->postJson('/api/v1/auth/register', [
                'name' => 'Integration User',
                'email' => 'integration@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertStatus(201);
        $userId = $response->json('data.user.id');
        $this->assertNotNull($userId);

        $response = $this->withHeaders(['Origin' => 'http://127.0.0.1:5173'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'integration@test.com',
                'password' => 'password123',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => 'integration@test.com']);

        $user = User::where('email', 'integration@test.com')->first();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/technologies', [
                'name' => 'Laravel',
                'color' => '#FF2D20',
            ]);

        $response->assertStatus(201);
        $techId = $response->json('data.id');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $techId,
                'title' => 'First study session',
                'started_at' => now()->subHours(2)->toIso8601String(),
                'ended_at' => now()->subHour()->toIso8601String(),
                'notes' => 'Learned about services',
                'mood' => 4,
                'focus_score' => 8,
            ]);

        $response->assertStatus(201);
        $sessionId = $response->json('data.id');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/study-sessions/'.$sessionId);

        $response->assertStatus(200);
        $this->assertEquals('First study session', $response->json('data.title'));

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/technologies');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));

        $aggregator = app(MetricsAggregator::class);
        Cache::tags(['analytics', "analytics:user:{$userId}"])->flush();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/analytics/dashboard');

        $response->assertStatus(200);
        $this->assertArrayHasKey('user_metrics', $response->json('data'));
        $this->assertArrayHasKey('technology_metrics', $response->json('data'));
    }

    public function test_session_start_end_flow_with_elapsed_seconds(): void
    {
        $user = User::factory()->create();
        $tech = Technology::forceCreate([
            'user_id' => $user->id,
            'name' => 'Vue.js',
            'slug' => 'vuejs',
            'color' => '#42B883',
            'is_active' => true,
        ]);
        $token = $user->createToken('api-token')->plainTextToken;

        $session = StudySession::factory()->active()->create([
            'user_id' => $user->id,
            'technology_id' => $tech->id,
            'started_at' => now()->subMinutes(5),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/study-sessions/active');

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data'));
        $this->assertArrayHasKey('elapsed_seconds', $response->json('data'));
        $this->assertGreaterThan(0, $response->json('data.elapsed_seconds'));

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/study-sessions/'.$session->id.'/end');

        $response->assertStatus(200);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/study-sessions/active');

        $response->assertStatus(200);
        $this->assertNull($response->json('data'));
    }

    public function test_multiple_sessions_across_technologies(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $tech1 = Technology::forceCreate([
            'user_id' => $user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);

        $tech2 = Technology::forceCreate([
            'user_id' => $user->id,
            'name' => 'JavaScript',
            'slug' => 'javascript',
            'color' => '#F7DF1E',
            'is_active' => true,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->postJson('/api/v1/study-sessions', [
                    'technology_id' => $tech1->id,
                    'title' => "PHP Session {$i}",
                    'started_at' => now()->subHours(4 + $i)->toIso8601String(),
                    'ended_at' => now()->subHours(3 + $i)->toIso8601String(),
                ])->assertStatus(201);
        }

        for ($i = 0; $i < 2; $i++) {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->postJson('/api/v1/study-sessions', [
                    'technology_id' => $tech2->id,
                    'title' => "JS Session {$i}",
                    'started_at' => now()->subHours(2 + $i)->toIso8601String(),
                    'ended_at' => now()->subHours(1 + $i)->toIso8601String(),
                ])->assertStatus(201);
        }

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/study-sessions?technology_id='.$tech1->id);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/study-sessions?technology_id='.$tech2->id);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_technology_deletion_preserves_session_history(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $tech = Technology::forceCreate([
            'user_id' => $user->id,
            'name' => 'Ruby',
            'slug' => 'ruby',
            'color' => '#CC342D',
            'is_active' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/study-sessions', [
                'technology_id' => $tech->id,
                'title' => 'Ruby study',
                'started_at' => now()->subHours(2)->toIso8601String(),
                'ended_at' => now()->subHour()->toIso8601String(),
            ])->assertStatus(201);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/technologies/'.$tech->id)
            ->assertStatus(200);

        $this->assertDatabaseHas('technologies', [
            'id' => $tech->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('study_sessions', [
            'technology_id' => $tech->id,
        ]);
    }
}
