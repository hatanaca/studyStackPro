<?php

namespace App\Events\StudySession;

use App\Models\StudySession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Evento disparado ao criar uma sessão. Listeners: invalidam cache, disparam recálculo de métricas. */
class StudySessionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly StudySession $session,
    ) {}
}
