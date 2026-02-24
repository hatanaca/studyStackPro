<?php

namespace App\Jobs;

use App\Events\Analytics\MetricsRecalculating;
use App\Modules\Analytics\Aggregators\MetricsAggregator;
use App\Modules\Analytics\Services\AnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecalculateMetricsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public int $timeout = 60;

    public string $queue = 'metrics';

    public int $uniqueFor = 5;

    public function uniqueId(): string
    {
        return $this->userId;
    }

    public function __construct(
        public readonly string $userId,
        public readonly bool $fullRecalc = true,
    ) {}

    public function handle(MetricsAggregator $aggregator, AnalyticsService $analyticsService): void
    {
        event(new MetricsRecalculating($this->userId));

        $aggregator->recalculateUserMetrics($this->userId);
        $aggregator->recalculateTechnologyMetrics($this->userId);
        $aggregator->recalculateDailyMinutes($this->userId);

        Cache::tags(['analytics', "user:{$this->userId}"])->flush();
        $dashboardData = $analyticsService->getDashboardData($this->userId);

        event(new \App\Events\Analytics\MetricsRecalculated($this->userId, $dashboardData));
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RecalculateMetricsJob failed', [
            'userId' => $this->userId,
            'attempt' => $this->attempts(),
            'error' => $e->getMessage(),
        ]);
    }
}
