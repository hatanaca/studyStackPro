<?php

namespace App\Events\StudySession;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;

/** Evento disparado ao deletar sessão. Usa userId/sessionId/durationMin (session já removida). */
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
