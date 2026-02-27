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

        $currentStreak = $this->calculateCurrentStreak($userId, $userTimezone);
        $maxStreak = $this->calculateMaxStreak($userId, $userTimezone);

        $totalSessions = $row?->total_sessions ?? 0;
        $totalMinutes = $row?->total_minutes ?? 0;
        $avgSessionMin = $row ? round((float) $row->avg_session_min, 2) : 0;
        $longestSessionMin = $row?->longest_session_min ?? 0;
        $shortestSessionMin = $row?->shortest_session_min ?? 0;
        $avgMood = $row?->avg_mood !== null ? round((float) $row->avg_mood, 2) : null;
        $avgFocusScore = $row?->avg_focus_score !== null ? round((float) $row->avg_focus_score, 2) : null;
        $lastSessionAt = $row?->last_session_at?->format('Y-m-d H:i:s');

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
            $currentStreak,
            $maxStreak,
            $avgMood,
            $avgFocusScore,
            $lastSessionAt,
        ]);
    }

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
            GROUP BY ss.user_id, (ss.started_at AT TIME ZONE ?)::date
            ON CONFLICT (user_id, study_date) DO UPDATE SET
                total_minutes = EXCLUDED.total_minutes,
                session_count = EXCLUDED.session_count,
                technologies = EXCLUDED.technologies,
                avg_mood = EXCLUDED.avg_mood,
                recalculated_at = NOW()
        ', [$userTimezone, $userId, $userTimezone]);
    }

    private function calculateCurrentStreak(string $userId, string $userTimezone = 'UTC'): int
    {
        $dates = DB::select('
            SELECT DISTINCT (started_at AT TIME ZONE ?)::date AS d
            FROM public.study_sessions
            WHERE user_id = ?::uuid AND ended_at IS NOT NULL
            ORDER BY d DESC
            LIMIT 365
        ', [$userTimezone, $userId]);

        if (empty($dates)) {
            return 0;
        }

        $streak = 0;
        $today = now()->timezone($userTimezone)->toDateString();

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

    private function calculateMaxStreak(string $userId, string $userTimezone = 'UTC'): int
    {
        $dates = DB::select('
            SELECT DISTINCT (started_at AT TIME ZONE ?)::date AS d
            FROM public.study_sessions
            WHERE user_id = ?::uuid AND ended_at IS NOT NULL
            ORDER BY d
        ', [$userTimezone, $userId]);

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
