<?php

namespace Tests\Feature\Achievements;

use App\Models\Achievement;
use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementControllerTest extends TestCase
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

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    private function createEndedSession(int $minutes, ?Carbon $startedAt = null): void
    {
        $started = $startedAt ?? now()->subMinutes($minutes);
        $tech = Technology::factory()->create(['user_id' => $this->user->id]);
        StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $tech->id,
            'started_at' => $started,
            'ended_at' => $started->copy()->addMinutes($minutes),
        ]);
    }

    public function test_index_lists_own_achievements(): void
    {
        Achievement::forceCreate([
            'user_id' => $this->user->id,
            'badge_key' => 'first_session',
            'title' => 'Primeira Sessão',
            'icon' => '🏅',
        ]);

        $response = $this->withHeaders($this->authHeaders())->getJson('/api/v1/achievements');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.badge_key', 'first_session');
    }

    public function test_check_awards_first_session_badge(): void
    {
        $this->createEndedSession(30);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/achievements/check');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.new_achievements.0.badge_key', 'first_session');

        $this->assertDatabaseHas('achievements', [
            'user_id' => $this->user->id,
            'badge_key' => 'first_session',
        ]);
    }

    public function test_check_is_idempotent_for_existing_badges(): void
    {
        $this->createEndedSession(30);

        $this->withHeaders($this->authHeaders())->postJson('/api/v1/achievements/check');
        $second = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/achievements/check');

        $second->assertStatus(200)
            ->assertJsonPath('data.count', 0);
    }
}
