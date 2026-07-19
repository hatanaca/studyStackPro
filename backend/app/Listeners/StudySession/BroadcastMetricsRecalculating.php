<?php

namespace App\Listeners\StudySession;

use App\Events\Analytics\MetricsRecalculating;
use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionUpdated;
use Illuminate\Support\Facades\Log;
use Throwable;

class BroadcastMetricsRecalculating
{
    /**
     * Dispara .metrics.recalculating imediatamente para o frontend exibir o spinner
     * antes do job de recálculo iniciar (job tem delay de 2s).
     */
    public function handle(StudySessionCreated|StudySessionUpdated $event): void
    {
        try {
            $userId = $event->session->user_id;
            event(new MetricsRecalculating($userId));
        } catch (Throwable $e) {
            Log::warning('Failed to broadcast metrics recalculating', [
                'user_id' => $event->session->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
