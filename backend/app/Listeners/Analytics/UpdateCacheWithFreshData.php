<?php

namespace App\Listeners\Analytics;

use App\Events\Analytics\MetricsRecalculated;
use App\Modules\Analytics\Services\AnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateCacheWithFreshData implements ShouldQueue
{
    public string $queue = 'metrics';

    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    public function handle(MetricsRecalculated $event): void
    {
        $userId = $event->userId;

        $this->analyticsService->getUserMetrics($userId);
        $this->analyticsService->getTechStats($userId);
        $this->analyticsService->getTimeSeries($userId, 7);
        $this->analyticsService->getTimeSeries($userId, 30);
        $this->analyticsService->getTimeSeries($userId, 90);
        $this->analyticsService->getWeekly($userId);
        $this->analyticsService->getHeatmap($userId);
    }
}
