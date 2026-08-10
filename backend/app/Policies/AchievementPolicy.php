<?php

namespace App\Policies;

use App\Models\Achievement;
use App\Models\User;

class AchievementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Achievement $achievement): bool
    {
        return $user->id === $achievement->user_id;
    }
}
