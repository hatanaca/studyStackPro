<?php

namespace App\Modules\Gamification\DTOs;

final readonly class AchievementDTO
{
    public function __construct(
        public string $userId,
        public string $badgeKey,
        public string $title,
        public ?string $description = null,
        public string $icon = '🏅',
        public ?array $metadata = null,
    ) {}
}
