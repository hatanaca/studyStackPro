<?php

namespace App\Listeners\StudySession;

use App\Events\StudySession\SessionEnded;
use App\Events\StudySession\StudySessionUpdated;
use Illuminate\Support\Facades\Log;
use Throwable;

class BroadcastSessionEnded
{
    public function handle(StudySessionUpdated $event): void
    {
        try {
            if (in_array('ended_at', $event->changedFields, true) && $event->session->ended_at !== null) {
                event(new SessionEnded($event->session));
            }
        } catch (Throwable $e) {
            Log::warning('Failed to broadcast session ended', [
                'session_id' => $event->session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
