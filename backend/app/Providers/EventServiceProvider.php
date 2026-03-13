<?php

namespace App\Providers;

/**
 * Mapeamento de eventos e listeners.
 * StudySession Created/Updated/Deleted: invalidam cache, disparam recálculo, broadcast (session started/ended, recalculating).
 * MetricsRecalculated: UpdateCacheWithFreshData, BroadcastMetricsUpdate.
 */
use App\Events\Analytics\MetricsRecalculated;
use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use App\Listeners\Analytics\BroadcastMetricsUpdate;
use App\Listeners\Analytics\UpdateCacheWithFreshData;
use App\Listeners\StudySession\BroadcastMetricsRecalculating;
use App\Listeners\StudySession\BroadcastSessionEnded;
use App\Listeners\StudySession\BroadcastSessionStarted;
use App\Listeners\StudySession\DispatchMetricsRecalculation;
use App\Listeners\StudySession\InvalidateSessionCache;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        StudySessionCreated::class => [
            InvalidateSessionCache::class,
            DispatchMetricsRecalculation::class,
            BroadcastSessionStarted::class,
            BroadcastMetricsRecalculating::class,
        ],
        StudySessionUpdated::class => [
            InvalidateSessionCache::class,
            DispatchMetricsRecalculation::class,
            BroadcastMetricsRecalculating::class,
            BroadcastSessionEnded::class,
        ],
        StudySessionDeleted::class => [
            InvalidateSessionCache::class,
            DispatchMetricsRecalculation::class,
            BroadcastMetricsRecalculating::class,
        ],
        MetricsRecalculated::class => [
            UpdateCacheWithFreshData::class,
            BroadcastMetricsUpdate::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
