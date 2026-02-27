<?php

namespace App\Listeners\StudySession;

use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use App\Jobs\RecalculateMetricsJob;

class DispatchMetricsRecalculation
{
    public function handle(StudySessionCreated|StudySessionUpdated|StudySessionDeleted $event): void
    {
        [$userId, $fullRecalc] = match (true) {
            $event instanceof StudySessionDeleted => [$event->userId, true],
            $event instanceof StudySessionUpdated => [
                $event->session->user_id,
                ! empty(array_intersect(
                    array_keys($event->changedFields ?? []),
                    ['started_at', 'ended_at', 'technology_id', 'duration_min']
                )),
            ],
            default => [$event->session->user_id, true],
        };

        RecalculateMetricsJob::dispatch($userId, $fullRecalc)
            ->onQueue('metrics')
            ->delay(now()->addSeconds(2));
    }
}
