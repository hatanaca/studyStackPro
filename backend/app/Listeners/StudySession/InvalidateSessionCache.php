<?php

namespace App\Listeners\StudySession;

use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use Illuminate\Support\Facades\Cache;

class InvalidateSessionCache
{
    public function handle(StudySessionCreated|StudySessionUpdated|StudySessionDeleted $event): void
    {
        $userId = $event instanceof StudySessionDeleted
            ? $event->userId
            : $event->session->user_id;
        Cache::tags(['sessions', "user:{$userId}"])->flush();
    }
}
