<?php

namespace App\Listeners\StudySession;

use App\Events\Analytics\MetricsRecalculating;
use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionUpdated;

class BroadcastMetricsRecalculating
{
    /**
     * Dispara .metrics.recalculating imediatamente para o frontend exibir o spinner
     * antes do job de recálculo iniciar (job tem delay de 2s).
     */
    public function handle(StudySessionCreated|StudySessionUpdated $event): void
    {
        $userId = $event->session->user_id;

        event(new MetricsRecalculating($userId));
    }
}
