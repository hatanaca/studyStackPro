<?php

namespace App\Modules\Analytics\Services;

use App\Jobs\RecalculateMetricsJob;
use App\Modules\Analytics\Repositories\Contracts\AnalyticsRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    public function __construct(
        private AnalyticsRepositoryInterface $repository
    ) {}

    public function getDashboardData(string $userId): array
    {
        $lockKey = 'dashboard:lock:'.$userId;

        return \Illuminate\Support\Facades\Cache::lock($lockKey, 10)->block(5, function () use ($userId) {
            return Cache::tags(['analytics', "user:{$userId}"])->remember(
                "dashboard:{$userId}",
                now()->addMinutes(5),
                fn () => $this->buildDashboardData($userId)
            );
        });
    }

    public function getUserMetrics(string $userId): array
    {
        return Cache::tags(['analytics', "user:{$userId}"])->remember(
            "user-metrics:{$userId}",
            now()->addMinutes(5),
            fn () => $this->repository->getUserMetrics($userId)
        );
    }

    public function getTechStats(string $userId): array
    {
        return Cache::tags(['analytics', "user:{$userId}"])->remember(
            "tech-stats:{$userId}",
            now()->addMinutes(5),
            fn () => $this->repository->getTechnologyMetrics($userId)
        );
    }

    public function getTimeSeries(string $userId, int $days = 30): array
    {
        $key = "time-series:{$userId}:{$days}";

        return Cache::tags(['analytics', "user:{$userId}"])->remember(
            $key,
            now()->addMinutes(15),
            fn () => $this->repository->getTimeSeries($userId, $days)
        );
    }

    public function getWeekly(string $userId): array
    {
        return Cache::tags(['analytics', "user:{$userId}"])->remember(
            "weekly:{$userId}",
            now()->addMinutes(15),
            fn () => $this->repository->getWeeklySummaries($userId)
        );
    }

    public function getHeatmap(string $userId, ?int $year = null): array
    {
        $year = $year ?? (int) now()->format('Y');
        $key = "heatmap:{$userId}:{$year}";

        return Cache::tags(['analytics', "user:{$userId}"])->remember(
            $key,
            now()->addHour(),
            fn () => $this->repository->getHeatmapData($userId, $year)
        );
    }

    public function dispatchRecalculate(string $userId): void
    {
        RecalculateMetricsJob::dispatch($userId, true);
    }

    private function buildDashboardData(string $userId): array
    {
        return [
            'user_metrics' => $this->repository->getUserMetrics($userId),
            'technology_metrics' => $this->repository->getTechnologyMetrics($userId),
            'time_series_30d' => $this->repository->getTimeSeries30d($userId),
            'top_technologies' => [],
        ];
    }
}
