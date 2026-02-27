<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateWeeklySummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public int $timeout = 120;

    public function handle(): void
    {
        // Agrega daily_minutes em weekly_summaries por usuário (ISO week, Monday = start)
        // Conforme spec: week_start, week_number, year, total_minutes, session_count, active_days
        DB::statement("
            INSERT INTO analytics.weekly_summaries (
                user_id, week_start, week_number, year,
                total_minutes, session_count, active_days, recalculated_at
            )
            SELECT
                user_id,
                date_trunc('week', study_date)::date AS week_start,
                EXTRACT(WEEK FROM date_trunc('week', study_date)::date)::smallint AS week_number,
                EXTRACT(ISOYEAR FROM date_trunc('week', study_date)::date)::smallint AS year,
                COALESCE(SUM(total_minutes), 0)::integer,
                COALESCE(SUM(session_count), 0)::integer,
                COUNT(DISTINCT study_date)::smallint AS active_days,
                NOW()
            FROM analytics.daily_minutes
            WHERE study_date >= CURRENT_DATE - interval '1 year'
            GROUP BY user_id, date_trunc('week', study_date)::date
            ON CONFLICT (user_id, week_start) DO UPDATE SET
                week_number = EXCLUDED.week_number,
                year = EXCLUDED.year,
                total_minutes = EXCLUDED.total_minutes,
                session_count = EXCLUDED.session_count,
                active_days = EXCLUDED.active_days,
                recalculated_at = NOW()
        ");
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateWeeklySummaryJob failed', [
            'attempt' => $this->attempts(),
            'error' => $e->getMessage(),
        ]);
    }
}
