<?php

namespace App\Policies;

use App\Models\Technology;
use App\Models\User;

class TechnologyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Technology $technology): bool
    {
        return $user->id === $technology->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Technology $technology): bool
    {
        return $user->id === $technology->user_id;
    }

    public function delete(User $user, Technology $technology): bool
    {
        return $user->id === $technology->user_id;
    }
}
