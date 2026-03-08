<?php

namespace App\Modules\Analytics\Repositories\Contracts;

interface AnalyticsRepositoryInterface
{
    public function getUserMetrics(string $userId): array;

    public function getTechnologyMetrics(string $userId): array;

    public function getTimeSeries30d(string $userId): array;

    /** @return array<int, array{date: string, total_minutes: int}> */
    public function getTimeSeries(string $userId, int $days): array;

    /** @return array<int, array{week_start: string, total_minutes: int, session_count: int}> */
    public function getWeeklySummaries(string $userId): array;

    /** @return array<int, array{date: string, total_minutes: int}> */
    public function getHeatmapData(string $userId, int $year): array;

    /**
     * Dados diários agregados para exportação em um intervalo de datas.
     *
     * @return array<int, array{date: string, total_minutes: int, session_count: int}>
     */
    public function getDailyMinutesByRange(string $userId, string $start, string $end): array;
}
