<?php

namespace App\Modules\Gamification\Repositories\Contracts;

use App\Models\Achievement;
use App\Modules\Gamification\DTOs\AchievementDTO;
use Illuminate\Support\Collection;

interface AchievementRepositoryInterface
{
    public function listForUser(string $userId): Collection;

    public function existingBadgeKeys(string $userId): array;

    public function create(AchievementDTO $dto): Achievement;

    public function existsForUser(string $userId, string $badgeKey): bool;
}
