<?php

namespace App\Events\Analytics;

use Illuminate\Foundation\Events\Dispatchable;

class MetricsRecalculated
{
    use Dispatchable;

    public function __construct(
        public readonly string $userId,
        public readonly array $dashboardData,
    ) {}
}
