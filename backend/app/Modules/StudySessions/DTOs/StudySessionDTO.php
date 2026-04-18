<?php

namespace App\Modules\StudySessions\DTOs;

use Carbon\Carbon;

/**
 * DTO de sessão de estudo.
 *
 * userId, technologyId, startedAt, endedAt (null = sessão ativa).
 * notes, mood, focusScore opcionais para enriquecimento.
 */
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
        public ?string $title = null,
    ) {}
}
