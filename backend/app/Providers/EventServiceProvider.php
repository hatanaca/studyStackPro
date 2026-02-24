<?php

namespace App\Providers;

use App\Events\Analytics\MetricsRecalculated;
use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use App\Listeners\StudySession\DispatchMetricsRecalculation;
use App\Listeners\StudySession\InvalidateSessionCache;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        StudySessionCreated::class => [
            InvalidateSessionCache::class,
            DispatchMetricsRecalculation::class,
        ],
        StudySessionUpdated::class => [
            InvalidateSessionCache::class,
            DispatchMetricsRecalculation::class,
        ],
        StudySessionDeleted::class => [
            InvalidateSessionCache::class,
            DispatchMetricsRecalculation::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
