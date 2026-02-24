<?php

namespace App\Events\StudySession;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;

class StudySessionDeleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $userId,
        public readonly string $sessionId,
        public readonly int $durationMin,
        public readonly Carbon $startedAt,
    ) {}
}
