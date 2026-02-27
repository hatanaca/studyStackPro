<?php

namespace App\Listeners\Analytics;

use App\Events\Analytics\MetricsRecalculated;
use Illuminate\Contracts\Queue\ShouldQueue;

class BroadcastMetricsUpdate implements ShouldQueue
{
    /**
     * O evento MetricsRecalculated já implementa ShouldBroadcast e é
     * transmitido automaticamente pelo Laravel. Este listener garante
     * conformidade com o fluxo event-driven e permite lógica adicional
     * pós-broadcast se necessário.
     */
    public function handle(MetricsRecalculated $event): void
    {
        // Broadcast já emitido pelo evento ShouldBroadcast.
        // Este listener pode ser usado para logging ou side-effects.
    }
}
