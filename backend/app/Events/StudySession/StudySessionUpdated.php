<?php

namespace App\Events\StudySession;

use App\Models\StudySession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudySessionUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly StudySession $session,
        public readonly array $changedFields = [],
    ) {}
}
