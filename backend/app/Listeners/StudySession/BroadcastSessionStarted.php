<?php

namespace App\Listeners\StudySession;

use App\Events\StudySession\SessionStarted;
use App\Events\StudySession\StudySessionCreated;

class BroadcastSessionStarted
{
    public function handle(StudySessionCreated $event): void
    {
        if ($event->session->ended_at === null) {
            event(new SessionStarted($event->session));
        }
    }
}
