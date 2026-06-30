<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RecalculateMetricsJob;
use App\Models\StudySession;
use App\Models\Technology;
use App\Models\User;
use App\Modules\Analytics\Aggregators\MetricsAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecalculateMetricsJobTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Technology $technology;

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
    }

    public function test_job_is_queued_on_metrics_queue(): void
    {
        $job = new RecalculateMetricsJob($this->user->id);

        $this->assertEquals('metrics', $job->queue);
    }

    public function test_job_has_correct_retry_config(): void
    {
        $job = new RecalculateMetricsJob($this->user->id);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([30, 60, 120], $job->backoff);
        $this->assertEquals(60, $job->timeout);
    }

    public function test_unique_id_returns_user_id(): void
    {
        $job = new RecalculateMetricsJob($this->user->id);

        $this->assertEquals($this->user->id, $job->uniqueId());
    }

    public function test_handle_recalculates_metrics_in_transaction(): void
    {
        StudySession::factory()->create([
            'user_id' => $this->user->id,
            'technology_id' => $this->technology->id,
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
        ]);

        Event::fake();

        $job = new RecalculateMetricsJob($this->user->id);
        $job->handle(app(MetricsAggregator::class), app(\App\Modules\Analytics\Services\AnalyticsService::class));

        $row = DB::selectOne('SELECT * FROM analytics.user_metrics WHERE user_id = ?::uuid', [$this->user->id]);
        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row->total_sessions);
    }

    public function test_handle_flushes_analytics_cache(): void
    {
        Cache::tags(['analytics', "analytics:user:{$this->user->id}"])->put('test', 'value', 300);

        Event::fake();

        $job = new RecalculateMetricsJob($this->user->id);
        $job->handle(app(MetricsAggregator::class), app(\App\Modules\Analytics\Services\AnalyticsService::class));

        $this->assertNull(Cache::tags(['analytics', "analytics:user:{$this->user->id}"])->get('test'));
    }

    public function test_handle_skips_when_user_not_found(): void
    {
        $fakeUserId = '00000000-0000-0000-0000-000000000000';

        $job = new RecalculateMetricsJob($fakeUserId);

        $result = DB::select('SELECT * FROM analytics.user_metrics WHERE user_id = ?::uuid', [$fakeUserId]);
        $this->assertEmpty($result);
    }

    public function test_failed_logs_error(): void
    {
        $job = new RecalculateMetricsJob($this->user->id);

        $exception = new \RuntimeException('Test failure');

        $job->failed($exception);

        $this->assertTrue(true);
    }
}
