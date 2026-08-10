<?php

namespace App\Modules\Gamification\Repositories;

use App\Models\Achievement;
use App\Modules\Gamification\DTOs\AchievementDTO;
use App\Modules\Gamification\Repositories\Contracts\AchievementRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class EloquentAchievementRepository implements AchievementRepositoryInterface
{
    private const CACHE_TTL_MINUTES = 5;

    public function listForUser(string $userId): Collection
    {
        $cacheKey = "achievements:list:{$userId}";

        return Cache::tags(['achievements', "achievements:user:{$userId}"])->remember(
            $cacheKey,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn () => Achievement::where('user_id', $userId)->orderByDesc('created_at')->get()
        );
    }

    public function existingBadgeKeys(string $userId): array
    {
        return Achievement::where('user_id', $userId)->pluck('badge_key')->toArray();
    }

    public function create(AchievementDTO $dto): Achievement
    {
        $achievement = Achievement::forceCreate([
            'user_id' => $dto->userId,
            'badge_key' => $dto->badgeKey,
            'title' => $dto->title,
            'description' => $dto->description,
            'icon' => $dto->icon,
            'metadata' => $dto->metadata,
        ]);
        $this->invalidateCacheForUser($dto->userId);

        return $achievement;
    }

    public function existsForUser(string $userId, string $badgeKey): bool
    {
        return Achievement::where('user_id', $userId)
            ->where('badge_key', $badgeKey)
            ->exists();
    }

    private function invalidateCacheForUser(string $userId): void
    {
        Cache::tags(['achievements', "achievements:user:{$userId}"])->flush();
    }
}
