<?php

namespace App\Listeners\Analytics;

use App\Events\Analytics\MetricsRecalculated;
use App\Modules\Analytics\Services\AnalyticsService;

class UpdateCacheWithFreshData
{
    public function handle(MetricsRecalculated $event): void
    {
        $analyticsService = app(AnalyticsService::class);
        $userId = $event->userId;

        $analyticsService->getUserMetrics($userId);
        $analyticsService->getTechStats($userId);
        $analyticsService->getTimeSeries($userId, 7);
        $analyticsService->getTimeSeries($userId, 30);
        $analyticsService->getTimeSeries($userId, 90);
        $analyticsService->getWeekly($userId);
        $analyticsService->getHeatmap($userId);
    }
}
