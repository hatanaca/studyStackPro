<?php

namespace App\Listeners\Analytics;

use App\Events\Analytics\MetricsRecalculated;
use Illuminate\Support\Facades\Log;

/**
 * Listener para MetricsRecalculated. O evento já implementa ShouldBroadcast
 * e é transmitido automaticamente pelo Laravel. Este listener registra um log
 * de confirmação do broadcast e serve de ponto de extensão para side-effects futuros.
 * Não implementa ShouldQueue — executa em memória para não gerar jobs desnecessários.
 */
class BroadcastMetricsUpdate
{
    public function handle(MetricsRecalculated $event): void
    {
        Log::channel('single')->debug('MetricsRecalculated broadcast emitido', [
            'user_id' => $event->userId,
        ]);
    }
}
