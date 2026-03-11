<?php

namespace App\Modules\Analytics\Services;

use App\Jobs\RecalculateMetricsJob;
use App\Modules\Analytics\Repositories\Contracts\AnalyticsRepositoryInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Serviço de analytics e métricas.
 *
 * Agrega dados do repositório com cache por tags (analytics, user:{id}). Usa lock para
 * evitar cache stampede no dashboard. TTLs: dashboard 5min, heatmap 1h, export sem cache.
 */
class AnalyticsService
{
    public function __construct(
        private AnalyticsRepositoryInterface $repository
    ) {}

    /**
     * Dados completos do dashboard. Cache com lock (evita stampede). TTL 5min.
     */
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

    /**
     * Métricas gerais do usuário (tempo total, streaks). Cache 5min.
     */
    public function getUserMetrics(string $userId): array
    {
        return Cache::tags(['analytics', "user:{$userId}"])->remember(
            "user-metrics:{$userId}",
            now()->addMinutes(5),
            fn () => $this->repository->getUserMetrics($userId)
        );
    }

    /**
     * Estatísticas por tecnologia. Cache 5min.
     */
    public function getTechStats(string $userId): array
    {
        return Cache::tags(['analytics', "user:{$userId}"])->remember(
            "tech-stats:{$userId}",
            now()->addMinutes(5),
            fn () => $this->repository->getTechnologyMetrics($userId)
        );
    }

    /**
     * Séries temporais (minutos por dia). Cache 15min.
     */
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

    /**
     * Heatmap (horas por dia da semana). Ano atual se não informado. Cache 1h.
     */
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

    /**
     * Dados para exportação no intervalo [start, end]. Sem cache para refletir dados atuais.
     *
     * @return array<int, array{date: string, total_minutes: int, session_count: int}>
     */
    public function getExportData(string $userId, string $start, string $end): array
    {
        return $this->repository->getDailyMinutesByRange($userId, $start, $end);
    }

    /**
     * @return array{job_id: string}
     */
    public function dispatchRecalculate(string $userId): array
    {
        $job = new RecalculateMetricsJob($userId, true);
        $job->onQueue('metrics');
        dispatch($job);

        return ['job_id' => method_exists($job, 'uuid') && $job->uuid() ? $job->uuid() : \Illuminate\Support\Str::uuid()->toString()];
    }

    /**
     * Monta payload completo do dashboard (métricas, tech, séries, top techs).
     */
    private function buildDashboardData(string $userId): array
    {
        $technologyMetrics = $this->repository->getTechnologyMetrics($userId);
        $topTechnologies = array_slice($technologyMetrics, 0, 5);

        return [
            'user_metrics' => $this->repository->getUserMetrics($userId),
            'technology_metrics' => $technologyMetrics,
            'time_series_30d' => $this->repository->getTimeSeries30d($userId),
            'top_technologies' => $topTechnologies,
        ];
    }
}
