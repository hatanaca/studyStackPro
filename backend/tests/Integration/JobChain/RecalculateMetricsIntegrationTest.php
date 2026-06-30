<?php

namespace Tests\Integration\JobChain;

use App\Jobs\RecalculateMetricsJob;
use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use App\Modules\Analytics\Aggregators\MetricsAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RecalculateMetricsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Technology $technology;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();
        $this->user = User::factory()->create();
        $this->technology = Technology::forceCreate([
            'user_id' => $this->user->id,
            'name' => 'PHP',
            'slug' => 'php',
            'color' => '#777BB4',
            'is_active' => true,
        ]);
    }

    public function test_job_recalculates_user_metrics(): void
    {
        StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->subHours(3),
            'ended_at' => now()->subHours(2),
            'mood' => 4,
            'focus_score' => 8,
        ]);

        StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
            'mood' => 5,
            'focus_score' => 9,
        ]);

        $job = new RecalculateMetricsJob($this->user->id);
        $job->handle(app(MetricsAggregator::class), app(\App\Modules\Analytics\Services\AnalyticsService::class));

        $row = DB::selectOne('SELECT * FROM analytics.user_metrics WHERE user_id = ?::uuid', [$this->user->id]);
        $this->assertNotNull($row);
        $this->assertSame(2, (int) $row->total_sessions);
        $this->assertGreaterThan(0, (int) $row->total_minutes);
        $this->assertNotNull($row->avg_mood);
        $this->assertNotNull($row->avg_focus_score);
    }

    public function test_job_recalculates_technology_metrics(): void
    {
        StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
        ]);

        $job = new RecalculateMetricsJob($this->user->id);
        $job->handle(app(MetricsAggregator::class), app(\App\Modules\Analytics\Services\AnalyticsService::class));

        $row = DB::selectOne(
            'SELECT * FROM analytics.technology_metrics WHERE user_id = ?::uuid AND technology_id = ?::uuid',
            [$this->user->id, $this->technology->id]
        );
        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row->session_count);
        $this->assertGreaterThan(0, (int) $row->total_minutes);
    }

    public function test_job_recalculates_daily_minutes(): void
    {
        StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->startOfDay()->addHours(10),
            'ended_at' => now()->startOfDay()->addHours(11),
        ]);

        $job = new RecalculateMetricsJob($this->user->id);
        $job->handle(app(MetricsAggregator::class), app(\App\Modules\Analytics\Services\AnalyticsService::class));

        $rows = DB::select('SELECT * FROM analytics.daily_minutes WHERE user_id = ?::uuid', [$this->user->id]);
        $this->assertNotEmpty($rows);
        $this->assertGreaterThan(0, (int) $rows[0]->total_minutes);
    }

    public function test_multiple_recalculations_are_idempotent(): void
    {
        StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
        ]);

        $job = new RecalculateMetricsJob($this->user->id);
        $aggregator = app(MetricsAggregator::class);
        $analyticsService = app(\App\Modules\Analytics\Services\AnalyticsService::class);

        $job->handle($aggregator, $analyticsService);
        $row1 = DB::selectOne('SELECT total_sessions FROM analytics.user_metrics WHERE user_id = ?::uuid', [$this->user->id]);

        $job->handle($aggregator, $analyticsService);
        $row2 = DB::selectOne('SELECT total_sessions FROM analytics.user_metrics WHERE user_id = ?::uuid', [$this->user->id]);

        $this->assertEquals($row1->total_sessions, $row2->total_sessions);
    }
}
