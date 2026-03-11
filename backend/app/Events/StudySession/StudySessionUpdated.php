<?php

namespace App\Events\StudySession;

use App\Models\StudySession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Evento disparado ao atualizar sessão. changedFields lista os campos alterados (para recálculo condicional). */
class StudySessionUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly StudySession $session,
        public readonly array $changedFields = [],
    ) {}
}
