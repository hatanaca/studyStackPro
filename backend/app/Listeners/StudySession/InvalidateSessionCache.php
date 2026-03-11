<?php

namespace App\Listeners\StudySession;

use App\Events\StudySession\StudySessionCreated;
use App\Events\StudySession\StudySessionDeleted;
use App\Events\StudySession\StudySessionUpdated;
use Illuminate\Support\Facades\Cache;

/** Listener que invalida cache de sessões (tags sessions, user:{id}) após CRUD. */
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
