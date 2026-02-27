<?php

namespace App\Modules\StudySessions\DTOs;

use Carbon\Carbon;

final readonly class StudySessionDTO
{
    public function __construct(
        public string $userId,
        public ?string $technologyId,
        public Carbon $startedAt,
        public ?Carbon $endedAt,
        public ?string $notes,
        public ?int $mood,
        public ?int $focusScore = null,
    ) {}
}
