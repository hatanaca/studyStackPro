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
                'current_streak_days' => 0,
                'max_streak_days' => 0,
                'last_session_at' => null,
            ];
        }
        $totalHours = round((float) $row->total_minutes / 60, 2);

        return [
            'total_sessions' => (int) $row->total_sessions,
            'total_minutes' => (int) $row->total_minutes,
            'total_hours' => $totalHours,
            'current_streak_days' => (int) $row->current_streak_days,
            'max_streak_days' => (int) ($row->max_streak_days ?? 0),
            'last_session_at' => $row->last_session_at ? \Carbon\Carbon::parse($row->last_session_at)->toIso8601String() : null,
        ];
    }

    public function getTechnologyMetrics(string $userId): array
    {
        $rows = DB::table('analytics.technology_metrics as tm')
            ->leftJoin('technologies as t', 't.id', '=', 'tm.technology_id')
            ->where('tm.user_id', $userId)
            ->select('tm.technology_id', 't.name as technology_name', 'tm.total_minutes', 'tm.session_count', 'tm.last_used_at')
            ->orderByDesc('tm.total_minutes')
            ->get();

        return $rows->map(fn ($r) => [
            'technology_id' => $r->technology_id,
            'technology_name' => $r->technology_name,
            'total_minutes' => (int) $r->total_minutes,
            'session_count' => (int) $r->session_count,
            'last_used_at' => $r->last_used_at ? \Carbon\Carbon::parse($r->last_used_at)->toIso8601String() : null,
        ])->all();
    }

    public function getTimeSeries30d(string $userId): array
    {
        return $this->getTimeSeries($userId, 30);
    }

    public function getTimeSeries(string $userId, int $days): array
    {
        $rows = DB::table('analytics.daily_minutes')
            ->where('user_id', $userId)
            ->where('date', '>=', now()->subDays($days)->startOfDay()->format('Y-m-d'))
            ->orderBy('date')
            ->get(['date', 'total_minutes']);

        return $rows->map(fn ($r) => [
            'date' => $r->date,
            'total_minutes' => (int) $r->total_minutes,
        ])->values()->all();
    }

    public function getWeeklySummaries(string $userId): array
    {
        $rows = DB::table('analytics.weekly_summaries')
            ->where('user_id', $userId)
            ->orderByDesc('week_start')
            ->limit(52)
            ->get(['week_start', 'total_minutes', 'session_count']);

        return $rows->map(fn ($r) => [
            'week_start' => $r->week_start,
            'total_minutes' => (int) $r->total_minutes,
            'session_count' => (int) $r->session_count,
        ])->values()->all();
    }

    public function getHeatmapData(string $userId, int $year): array
    {
        $rows = DB::table('analytics.daily_minutes')
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->orderBy('date')
            ->get(['date', 'total_minutes']);

        return $rows->map(fn ($r) => [
            'date' => $r->date,
            'total_minutes' => (int) $r->total_minutes,
        ])->values()->all();
    }
}
