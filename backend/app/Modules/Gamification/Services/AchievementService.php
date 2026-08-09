<?php

namespace App\Modules\Gamification\Services;

use App\Models\Achievement;
use App\Models\User;
use App\Modules\Gamification\DTOs\AchievementDTO;
use App\Modules\Gamification\Repositories\Contracts\AchievementRepositoryInterface;
use Illuminate\Support\Collection;

class AchievementService
{
    public function __construct(
        private AchievementRepositoryInterface $achievementRepository
    ) {}

    public function getUserAchievements(string $userId): Collection
    {
        return $this->achievementRepository->listForUser($userId);
    }

    public function checkAndAward(string $userId): array
    {
        $user = User::find($userId);
        if (! $user) {
            return [];
        }

        $existing = $this->achievementRepository->existingBadgeKeys($userId);
        $newAchievements = [];

        $sessionsCount = $user->studySessions()->whereNotNull('ended_at')->count();
        $totalMinutes = (int) $user->studySessions()->whereNotNull('ended_at')->sum('duration_min');
        $totalHours = $totalMinutes / 60;

        // Session count badges
        $sessionBadges = [
            'first_session' => 1,
            'sessions_10' => 10,
            'sessions_50' => 50,
            'sessions_100' => 100,
        ];
        foreach ($sessionBadges as $key => $threshold) {
            if (! in_array($key, $existing) && $sessionsCount >= $threshold) {
                $newAchievements[] = $this->awardBadge($userId, $key, ['sessions' => $sessionsCount]);
            }
        }

        // Hour badges
        $hourBadges = [
            'hours_10' => 10,
            'hours_50' => 50,
            'hours_100' => 100,
        ];
        foreach ($hourBadges as $key => $threshold) {
            if (! in_array($key, $existing) && $totalHours >= $threshold) {
                $newAchievements[] = $this->awardBadge($userId, $key, ['hours' => round($totalHours, 1)]);
            }
        }

        return $newAchievements;
    }

    private function awardBadge(string $userId, string $badgeKey, array $metadata = []): Achievement
    {
        $badge = config('gamification.badges', [])[$badgeKey] ?? [
            'title' => $badgeKey,
            'description' => null,
            'icon' => '🏅',
        ];

        return $this->achievementRepository->create(new AchievementDTO(
            userId: $userId,
            badgeKey: $badgeKey,
            title: $badge['title'],
            description: $badge['description'],
            icon: $badge['icon'],
            metadata: $metadata,
        ));
    }
}
