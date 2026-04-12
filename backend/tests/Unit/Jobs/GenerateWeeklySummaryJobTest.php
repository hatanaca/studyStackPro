<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateWeeklySummaryJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GenerateWeeklySummaryJobTest extends TestCase
{

    public function test_job_is_on_scheduler_queue(): void
    {
        $job = new GenerateWeeklySummaryJob();

        $this->assertSame('scheduler', $job->queue);
    }

    public function test_job_has_retry_configuration(): void
    {
        $job = new GenerateWeeklySummaryJob();

        $this->assertSame(3, $job->tries);
        $this->assertSame(120, $job->timeout);
        $this->assertCount(3, $job->backoff);
    }

    public function test_handle_executes_insert_on_conflict_query(): void
    {
        DB::shouldReceive('statement')
            ->once()
            ->withArgs(function (string $sql) {
                return str_contains($sql, 'INSERT INTO analytics.weekly_summaries')
                    && str_contains($sql, 'ON CONFLICT (user_id, week_start) DO UPDATE');
            });

        $job = new GenerateWeeklySummaryJob();
        $job->handle();
    }

    public function test_failed_logs_error(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'GenerateWeeklySummaryJob failed'
                    && isset($context['error']);
            });

        $job = new GenerateWeeklySummaryJob();
        $job->failed(new \RuntimeException('DB connection lost'));
    }
}
