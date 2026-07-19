<?php

namespace App\Listeners\StudySession;

use App\Events\StudySession\SessionStarted;
use App\Events\StudySession\StudySessionCreated;
use Illuminate\Support\Facades\Log;
use Throwable;

class BroadcastSessionStarted
{
    public function handle(StudySessionCreated $event): void
    {
        try {
            if ($event->session->ended_at === null) {
                event(new SessionStarted($event->session));
            }
        } catch (Throwable $e) {
            Log::warning('Failed to broadcast session started', [
                'session_id' => $event->session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
