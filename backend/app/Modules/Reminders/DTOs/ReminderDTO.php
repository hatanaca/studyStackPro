<?php

namespace App\Modules\Reminders\DTOs;

final readonly class ReminderDTO
{
    public function __construct(
        public string $text,
        public ?string $technologyId = null,
        public ?bool $completed = null,
    ) {}
}
