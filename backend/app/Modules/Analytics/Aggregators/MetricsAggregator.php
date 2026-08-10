<?php

namespace App\Modules\Analytics\Aggregators;

use Illuminate\Support\Facades\DB;

/**
 * Agrega métricas a partir de study_sessions para o schema analytics.
 * Usado pelo RecalculateMetricsJob.
 * Conforme spec: usa timezone do usuário para study_date e streaks.
 */
class MetricsAggregator
{
    /** Recalcula métricas gerais do usuário e insere em analytics.user_metrics */
    public function recalculateUserMetrics(string $userId, string $userTimezone = 'UTC'): void
    {
        $row = DB::selectOne('
            SELECT
                COUNT(*)::int AS total_sessions,
                COALESCE(SUM(duration_min), 0)::int AS total_minutes,
                COALESCE(AVG(duration_min), 0)::numeric AS avg_session_min,
                COALESCE(MAX(duration_min), 0)::int AS longest_session_min,
                COALESCE(MIN(duration_min), 0)::int AS shortest_session_min,
                AVG(CASE WHEN mood IS NOT NULL THEN mood::numeric END) AS avg_mood,
                AVG(CASE WHEN focus_score IS NOT NULL THEN focus_score::numeric END) AS avg_focus_score,
                MAX(ended_at) AS last_session_at
            FROM public.study_sessions
            WHERE user_id = ?::uuid AND ended_at IS NOT NULL
        ', [$userId]);

        $streaks = $this->calculateStreaks($userId, $userTimezone);

        $totalSessions = $row->total_sessions ?? 0;
        $totalMinutes = $row->total_minutes ?? 0;
        $avgSessionMin = $row ? round((float) $row->avg_session_min, 2) : 0;
        $longestSessionMin = $row->longest_session_min ?? 0;
        $shortestSessionMin = $row->shortest_session_min ?? 0;
        $avgMood = $row?->avg_mood !== null ? round((float) $row->avg_mood, 2) : null;
        $avgFocusScore = $row?->avg_focus_score !== null ? round((float) $row->avg_focus_score, 2) : null;
        $raw = $row?->last_session_at;
        $lastSessionAt = $raw instanceof \DateTimeInterface ? $raw->format('Y-m-d H:i:s') : $raw;

        DB::statement('
            INSERT INTO analytics.user_metrics (
                user_id, total_sessions, total_minutes, avg_session_min,
                longest_session_min, shortest_session_min, current_streak_days, max_streak_days,
                avg_mood, avg_focus_score, last_session_at, recalculated_at
            )
            VALUES (?::uuid, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::timestamptz, NOW())
            ON CONFLICT (user_id) DO UPDATE SET
                total_sessions = EXCLUDED.total_sessions,
                total_minutes = EXCLUDED.total_minutes,
                avg_session_min = EXCLUDED.avg_session_min,
                longest_session_min = EXCLUDED.longest_session_min,
                shortest_session_min = EXCLUDED.shortest_session_min,
                current_streak_days = EXCLUDED.current_streak_days,
                max_streak_days = EXCLUDED.max_streak_days,
                avg_mood = EXCLUDED.avg_mood,
                avg_focus_score = EXCLUDED.avg_focus_score,
                last_session_at = EXCLUDED.last_session_at,
                recalculated_at = NOW()
        ', [
            $userId,
            $totalSessions,
            $totalMinutes,
            $avgSessionMin,
            $longestSessionMin,
            $shortestSessionMin,
            $streaks['current'],
            $streaks['max'],
            $avgMood,
            $avgFocusScore,
            $lastSessionAt,
        ]);
    }

    /** Recalcula métricas por tecnologia e upsert em analytics.technology_metrics */
    public function recalculateTechnologyMetrics(string $userId): void
    {
        DB::statement('
            WITH user_total AS (
                SELECT COALESCE(SUM(duration_min), 1)::numeric AS total
                FROM public.study_sessions
                WHERE user_id = ?::uuid AND ended_at IS NOT NULL
            )
            INSERT INTO analytics.technology_metrics (
                user_id, technology_id, total_minutes, session_count,
                avg_session_min, percentage_total, first_studied_at, last_studied_at, recalculated_at
            )
            SELECT
                ss.user_id,
                ss.technology_id,
                COALESCE(SUM(ss.duration_min), 0),
                COUNT(*)::int,
                COALESCE(AVG(ss.duration_min), 0),
                ROUND((COALESCE(SUM(ss.duration_min), 0)::numeric / ut.total) * 100, 2),
                MIN(ss.started_at),
                MAX(ss.ended_at),
                NOW()
            FROM public.study_sessions ss, user_total ut
            WHERE ss.user_id = ?::uuid
              AND ss.ended_at IS NOT NULL
              AND ss.technology_id IS NOT NULL
            GROUP BY ss.user_id, ss.technology_id, ut.total
            ON CONFLICT (user_id, technology_id) DO UPDATE SET
                total_minutes = EXCLUDED.total_minutes,
                session_count = EXCLUDED.session_count,
                avg_session_min = EXCLUDED.avg_session_min,
                percentage_total = EXCLUDED.percentage_total,
                first_studied_at = EXCLUDED.first_studied_at,
                last_studied_at = EXCLUDED.last_studied_at,
                recalculated_at = NOW()
        ', [$userId, $userId]);
    }

    /** Recalcula minutos por dia (schema analytics.daily_minutes). study_date no timezone do usuário. */
    public function recalculateDailyMinutes(string $userId, string $userTimezone = 'UTC'): void
    {
        DB::statement('
            INSERT INTO analytics.daily_minutes (
                user_id, study_date, total_minutes, session_count, technologies, avg_mood, recalculated_at
            )
            SELECT
                ss.user_id,
                (ss.started_at AT TIME ZONE ?)::date,
                COALESCE(SUM(ss.duration_min), 0),
                COUNT(*)::int,
                COALESCE(
                    array_remove(array_agg(DISTINCT ss.technology_id) FILTER (WHERE ss.technology_id IS NOT NULL), NULL),
                    \'{}\'::uuid[]
                ),
                AVG(ss.mood) FILTER (WHERE ss.mood IS NOT NULL),
                NOW()
            FROM public.study_sessions ss
            WHERE ss.user_id = ?::uuid AND ss.ended_at IS NOT NULL
            GROUP BY 1, 2
            ON CONFLICT (user_id, study_date) DO UPDATE SET
                total_minutes = EXCLUDED.total_minutes,
                session_count = EXCLUDED.session_count,
                technologies = EXCLUDED.technologies,
                avg_mood = EXCLUDED.avg_mood,
                recalculated_at = NOW()
        ', [$userTimezone, $userId]);
    }

    /**
     * Calcula streak atual e máximo em uma única query (2 table scans → 1).
     */
    private function calculateStreaks(string $userId, string $userTimezone = 'UTC'): array
    {
        $dates = DB::select('
            SELECT DISTINCT (started_at AT TIME ZONE ?)::date AS d
            FROM public.study_sessions
            WHERE user_id = ?::uuid AND ended_at IS NOT NULL
              AND started_at >= NOW() - INTERVAL \'730 days\'
            ORDER BY d DESC
        ', [$userTimezone, $userId]);

        if (empty($dates)) {
            return ['current' => 0, 'max' => 0];
        }

        $today = now()->timezone($userTimezone)->toDateString();
        $current = 0;
        $max = 0;
        $streak = 0;
        $prevDate = null;

        foreach ($dates as $row) {
            $d = is_object($row->d) ? $row->d->format('Y-m-d') : $row->d;

            // Current streak: must start from today
            if ($current === 0 && $d === $today) {
                $current = 1;
            } elseif ($current > 0) {
                $expected = date('Y-m-d', strtotime($today.' -'.$current.' days'));
                if ($d === $expected) {
                    $current++;
                }
            }

            // Max streak: count consecutive days
            if ($prevDate !== null) {
                $diff = (strtotime($prevDate) - strtotime($d)) / 86400;
                if ($diff === 1) {
                    $streak++;
                } else {
                    $max = max($max, $streak);
                    $streak = 1;
                }
            } else {
                $streak = 1;
            }

            $prevDate = $d;
        }

        $max = max($max, $streak);

        return ['current' => $current, 'max' => $max];
    }
}
