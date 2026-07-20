<?php

namespace App\Modules\Goals\DTOs;

final readonly class GoalDTO
{
    public function __construct(
        public string $userId,
        public string $type,
        public int $targetValue,
        public string $startDate,
        public ?string $endDate = null,
        public ?array $meta = null,
    ) {}
}
