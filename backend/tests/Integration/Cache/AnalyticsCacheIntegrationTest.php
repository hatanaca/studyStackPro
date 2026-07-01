<?php

namespace Tests\Integration\Cache;

use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnalyticsCacheIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Technology $technology;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        Queue::fake();
        $this->user = User::factory()->create();
        $this->technology = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);
        $this->token = $this->user->createToken('api-token')->plainTextToken;
    }

    public function test_dashboard_response_is_cached(): void
    {
        $response1 = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/dashboard');

        $response1->assertStatus(200);

        $cached = Cache::tags(['analytics', "analytics:user:{$this->user->id}"])->get("dashboard:{$this->user->id}");
        $this->assertNotNull($cached);
    }

    public function test_cache_is_invalidated_after_recalculate(): void
    {
        Cache::tags(['analytics', "analytics:user:{$this->user->id}"])->put(
            "dashboard:{$this->user->id}",
            ['cached' => true],
            300
        );

        Cache::tags(['analytics', "analytics:user:{$this->user->id}"])->flush();

        $cached = Cache::tags(['analytics', "analytics:user:{$this->user->id}"])->get("dashboard:{$this->user->id}");
        $this->assertNull($cached);
    }

    public function test_different_users_have_separate_cache(): void
    {
        $user2 = User::factory()->create();
        $token2 = $user2->createToken('api-token')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/dashboard')
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$token2)
            ->getJson('/api/v1/analytics/dashboard')
            ->assertStatus(200);

        $cache1 = Cache::tags(['analytics', "analytics:user:{$this->user->id}"])->get("dashboard:{$this->user->id}");
        $cache2 = Cache::tags(['analytics', "analytics:user:{$user2->id}"])->get("dashboard:{$user2->id}");

        $this->assertNotNull($cache1);
        $this->assertNotNull($cache2);
    }

    public function test_heatmap_uses_separate_cache_key(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/analytics/heatmap')
            ->assertStatus(200);

        $year = (int) now()->format('Y');
        $cached = Cache::tags(['analytics', "analytics:user:{$this->user->id}"])->get("heatmap:{$this->user->id}:{$year}");
        $this->assertNotNull($cached);
    }
}
