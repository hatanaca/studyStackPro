<?php

namespace App\Listeners\Analytics;

use App\Events\Analytics\MetricsRecalculated;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Listener para MetricsRecalculated. O evento já implementa ShouldBroadcast
 * e é transmitido automaticamente. Este listener mantém o fluxo event-driven
 * e permite logging ou side-effects extras no futuro.
 */
class BroadcastMetricsUpdate implements ShouldQueue
{
    public function handle(MetricsRecalculated $event): void
    {
        // Broadcast já emitido pelo evento ShouldBroadcast.
        // Este listener pode ser usado para logging ou side-effects.
    }
}
