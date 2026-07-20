<?php

namespace App\Modules\Notifications\DTOs;

final readonly class NotificationDTO
{
    public function __construct(
        public string $userId,
        public string $type,
        public string $title,
        public ?string $message = null,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
    ) {}
}
