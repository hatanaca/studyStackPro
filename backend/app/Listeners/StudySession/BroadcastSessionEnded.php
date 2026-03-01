<?php

namespace App\Listeners\StudySession;

use App\Events\StudySession\SessionEnded;
use App\Events\StudySession\StudySessionUpdated;

class BroadcastSessionEnded
{
    public function handle(StudySessionUpdated $event): void
    {
        if (in_array('ended_at', $event->changedFields, true) && $event->session->ended_at !== null) {
            event(new SessionEnded($event->session));
        }
    }
}
