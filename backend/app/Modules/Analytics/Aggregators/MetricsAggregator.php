<?php

namespace App\Modules\Analytics\Aggregators;

use Illuminate\Support\Facades\DB;

/**
 * Agrega métricas a partir de study_sessions para o schema analytics.
 * Usado pelo RecalculateMetricsJob.
 */
class MetricsAggregator
{
    public function recalculateUserMetrics(string $userId): void
    {
        $row = DB::selectOne('
            SELECT
                COALESCE(COUNT(*) FILTER (WHERE ended_at IS NOT NULL), 0)::int AS total_sessions,
                COALESCE(SUM(duration_min) FILTER (WHERE ended_at IS NOT NULL), 0)::int AS total_minutes,
                MAX(ended_at) FILTER (WHERE ended_at IS NOT NULL) AS last_session_at
            FROM public.study_sessions
            WHERE user_id = ?::uuid
        ', [$userId]);

        $totalSessions = $row->total_sessions ?? 0;
        $totalMinutes = $row->total_minutes ?? 0;
        $lastSessionAt = $row->last_session_at?->format('Y-m-d H:i:s');
        $currentStreak = $this->calculateCurrentStreak($userId);
        $maxStreak = $this->calculateMaxStreak($userId);
        $now = now()->toIso8601String();

        DB::statement('
            INSERT INTO analytics.user_metrics (user_id, total_sessions, total_minutes, current_streak_days, max_streak_days, last_session_at, updated_at)
            VALUES (?::uuid, ?, ?, ?, ?, ?::timestamptz, ?::timestamptz)
            ON CONFLICT (user_id) DO UPDATE SET
                total_sessions = EXCLUDED.total_sessions,
                total_minutes = EXCLUDED.total_minutes,
                current_streak_days = EXCLUDED.current_streak_days,
                max_streak_days = EXCLUDED.max_streak_days,
                last_session_at = EXCLUDED.last_session_at,
                updated_at = EXCLUDED.updated_at
        ', [$userId, $totalSessions, $totalMinutes, $currentStreak, $maxStreak, $lastSessionAt, $now]);
    }

    public function recalculateTechnologyMetrics(string $userId): void
    {
        $now = now()->toIso8601String();

        DB::statement('
            INSERT INTO analytics.technology_metrics (user_id, technology_id, total_minutes, session_count, last_used_at, updated_at)
            SELECT
                ss.user_id,
                ss.technology_id,
                COALESCE(SUM(ss.duration_min), 0),
                COUNT(*),
                MAX(ss.ended_at),
                ?::timestamptz
            FROM public.study_sessions ss
            WHERE ss.user_id = ?::uuid AND ss.technology_id IS NOT NULL AND ss.ended_at IS NOT NULL
            GROUP BY ss.user_id, ss.technology_id
            ON CONFLICT (user_id, technology_id) DO UPDATE SET
                total_minutes = EXCLUDED.total_minutes,
                session_count = EXCLUDED.session_count,
                last_used_at = EXCLUDED.last_used_at,
                updated_at = EXCLUDED.updated_at
        ', [$now, $userId]);

    }

    public function recalculateDailyMinutes(string $userId): void
    {
        $now = now()->toIso8601String();

        DB::statement('
            INSERT INTO analytics.daily_minutes (user_id, date, total_minutes, updated_at)
            SELECT
                ss.user_id,
                (ss.started_at AT TIME ZONE \'UTC\')::date,
                COALESCE(SUM(ss.duration_min), 0),
                ?::timestamptz
            FROM public.study_sessions ss
            WHERE ss.user_id = ?::uuid AND ss.ended_at IS NOT NULL
            GROUP BY ss.user_id, (ss.started_at AT TIME ZONE \'UTC\')::date
            ON CONFLICT (user_id, date) DO UPDATE SET
                total_minutes = EXCLUDED.total_minutes,
                updated_at = EXCLUDED.updated_at
        ', [$now, $userId]);
    }

    private function calculateCurrentStreak(string $userId): int
    {
        $dates = DB::select('
            SELECT DISTINCT (started_at AT TIME ZONE ?)::date AS d
            FROM public.study_sessions
            WHERE user_id = ?::uuid AND ended_at IS NOT NULL
            ORDER BY d DESC
            LIMIT 365
        ', [config('app.timezone', 'UTC'), $userId]);

        if (empty($dates)) {
            return 0;
        }

        $streak = 0;
        $today = now()->timezone(config('app.timezone'))->toDateString();

        foreach ($dates as $row) {
            $d = $row->d;
            if (is_object($d)) {
                $d = $d->format('Y-m-d');
            }
            $expected = date('Y-m-d', strtotime($today.' -'.$streak.' days'));
            if ($d === $expected) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    private function calculateMaxStreak(string $userId): int
    {
        $dates = DB::select('
            SELECT DISTINCT (started_at AT TIME ZONE ?)::date AS d
            FROM public.study_sessions
            WHERE user_id = ?::uuid AND ended_at IS NOT NULL
            ORDER BY d
        ', [config('app.timezone', 'UTC'), $userId]);

        if (count($dates) < 2) {
            return count($dates);
        }

        $maxStreak = 1;
        $current = 1;
        for ($i = 1; $i < count($dates); $i++) {
            $prev = is_object($dates[$i - 1]->d) ? $dates[$i - 1]->d->format('Y-m-d') : $dates[$i - 1]->d;
            $curr = is_object($dates[$i]->d) ? $dates[$i]->d->format('Y-m-d') : $dates[$i]->d;
            $diff = (strtotime($curr) - strtotime($prev)) / 86400;
            if ($diff === 1) {
                $current++;
            } else {
                $maxStreak = max($maxStreak, $current);
                $current = 1;
            }
        }

        return max($maxStreak, $current);
    }
}
