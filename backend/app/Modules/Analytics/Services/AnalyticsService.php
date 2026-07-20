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
        return $this->buildDashboardData($userId);
    }

    public function getTechnologyMetrics(string $userId): array
    {
        return $this->repository->getTechnologyMetrics($userId);
    }

    public function getUserMetrics(string $userId): array
    {
        return $this->repository->getUserMetrics($userId);
    }

    public function getHeatmapData(string $userId, int $year): array
    {
        return $this->repository->getHeatmapData($userId, $year);
    }

    public function getTimeSeries30d(string $userId): array
    {
        return $this->repository->getTimeSeries30d($userId);
    }

    public function getTimeSeries(string $userId, int $days = 30): array
    {
        return $this->repository->getTimeSeries($userId, $days);
    }

    public function getWeeklySummaries(string $userId): array
    {
        return $this->repository->getWeeklySummaries($userId);
    }

    public function getDailyMinutesByRange(string $userId, string $start, string $end): array
    {
        return $this->repository->getDailyMinutesByRange($userId, $start, $end);
    }

    public function dispatchRecalculate(string $userId): array
    {
        $job = new RecalculateMetricsJob($userId, true);
        $job->onQueue('metrics');
        $jobId = dispatch($job);

        return ['job_id' => $jobId];
    }

    private function buildDashboardData(string $userId): array
    {
        return Cache::tags(["analytics:user:{$userId}"])->remember(
            "analytics:dashboard:{$userId}",
            now()->addMinutes(5),
            function () use ($userId) {
                $technologyMetrics = $this->repository->getTechnologyMetrics($userId);
                $topTechnologies = array_slice($technologyMetrics, 0, 5);

                return [
                    'user_metrics' => $this->repository->getUserMetrics($userId),
                    'technology_metrics' => $technologyMetrics,
                    'time_series_30d' => $this->repository->getTimeSeries30d($userId),
                    'top_technologies' => $topTechnologies,
                ];
            }
        );
    }
}
