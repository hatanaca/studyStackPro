<?php

namespace App\Modules\Analytics\Repositories;

use App\Modules\Analytics\Repositories\Contracts\AnalyticsRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentAnalyticsRepository implements AnalyticsRepositoryInterface
{
    public function getUserMetrics(string $userId): array
    {
        $row = DB::table('analytics.user_metrics')->where('user_id', $userId)->first();
        if (! $row) {
            return [
                'total_sessions' => 0,
                'total_minutes' => 0,
                'total_hours' => 0,
                'avg_session_min' => 0,
                'longest_session_min' => 0,
                'shortest_session_min' => 0,
                'current_streak_days' => 0,
                'max_streak_days' => 0,
                'avg_mood' => null,
                'avg_focus_score' => null,
                'last_session_at' => null,
            ];
        }

        return [
            'total_sessions' => (int) $row->total_sessions,
            'total_minutes' => (int) $row->total_minutes,
            'total_hours' => (float) ($row->total_hours ?? $row->total_minutes / 60),
            'avg_session_min' => (float) ($row->avg_session_min ?? 0),
            'longest_session_min' => (int) ($row->longest_session_min ?? 0),
            'shortest_session_min' => (int) ($row->shortest_session_min ?? 0),
            'current_streak_days' => (int) $row->current_streak_days,
            'max_streak_days' => (int) ($row->max_streak_days ?? 0),
            'avg_mood' => $row->avg_mood !== null ? (float) $row->avg_mood : null,
            'avg_focus_score' => $row->avg_focus_score !== null ? (float) $row->avg_focus_score : null,
            'last_session_at' => $row->last_session_at ? \Carbon\Carbon::parse($row->last_session_at)->toIso8601String() : null,
        ];
    }

    public function getTechnologyMetrics(string $userId): array
    {
        $rows = DB::table('analytics.technology_metrics as tm')
            ->leftJoin('technologies as t', 't.id', '=', 'tm.technology_id')
            ->where('tm.user_id', $userId)
            ->select(
                'tm.technology_id',
                't.name as technology_name',
                't.slug as technology_slug',
                't.color as technology_color',
                'tm.total_minutes',
                'tm.total_hours',
                'tm.session_count',
                'tm.avg_session_min',
                'tm.percentage_total',
                'tm.last_studied_at'
            )
            ->orderByDesc('tm.total_minutes')
            ->get();

        return $rows->map(fn ($r) => [
            'technology' => $r->technology_id ? [
                'id' => $r->technology_id,
                'name' => $r->technology_name,
                'slug' => $r->technology_slug ?? '',
                'color' => $r->technology_color ?? '#3498DB',
            ] : null,
            'technology_id' => $r->technology_id,
            'total_minutes' => (int) $r->total_minutes,
            'total_hours' => (float) ($r->total_hours ?? $r->total_minutes / 60),
            'session_count' => (int) $r->session_count,
            'avg_session_min' => (float) ($r->avg_session_min ?? 0),
            'percentage_total' => (float) ($r->percentage_total ?? 0),
            'last_studied_at' => $r->last_studied_at ? \Carbon\Carbon::parse($r->last_studied_at)->toIso8601String() : null,
        ])->all();
    }

    public function getTimeSeries30d(string $userId): array
    {
        return $this->getTimeSeries($userId, 30);
    }

    public function getTimeSeries(string $userId, int $days): array
    {
        // Série temporal completa: todos os dias do range, com ou sem estudo (conforme spec)
        $rows = DB::select("
            SELECT
                d.day::date AS study_date,
                COALESCE(dm.total_minutes, 0)::int AS total_minutes,
                COALESCE(dm.session_count, 0)::int AS session_count
            FROM generate_series(
                CURRENT_DATE - INTERVAL '1 day' * ?,
                CURRENT_DATE,
                INTERVAL '1 day'
            ) AS d(day)
            LEFT JOIN analytics.daily_minutes dm
                ON dm.study_date = d.day::date
                AND dm.user_id = ?::uuid
            ORDER BY d.day ASC
        ", [$days - 1, $userId]);

        return array_map(fn ($r) => [
            'date' => $r->study_date,
            'total_minutes' => (int) $r->total_minutes,
            'session_count' => (int) $r->session_count,
        ], $rows);
    }

    public function getWeeklySummaries(string $userId): array
    {
        $rows = DB::table('analytics.weekly_summaries')
            ->where('user_id', $userId)
            ->orderByDesc('week_start')
            ->limit(52)
            ->get(['week_start', 'week_number', 'year', 'total_minutes', 'session_count', 'active_days']);

        return $rows->map(fn ($r) => [
            'week_start' => $r->week_start,
            'week_number' => (int) ($r->week_number ?? 0),
            'year' => (int) ($r->year ?? 0),
            'total_minutes' => (int) $r->total_minutes,
            'session_count' => (int) $r->session_count,
            'active_days' => (int) ($r->active_days ?? 0),
        ])->values()->all();
    }

    public function getHeatmapData(string $userId, int $year): array
    {
        $rows = DB::table('analytics.daily_minutes')
            ->where('user_id', $userId)
            ->whereRaw('EXTRACT(YEAR FROM study_date) = ?', [$year])
            ->orderBy('study_date')
            ->get(['study_date', 'total_minutes']);

        return $rows->map(fn ($r) => [
            'date' => $r->study_date,
            'total_minutes' => (int) $r->total_minutes,
        ])->all();
    }
}
