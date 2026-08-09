<?php

namespace App\Policies;

use App\Models\StudyPath;
use App\Models\User;

class StudyPathPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StudyPath $studyPath): bool
    {
        return $user->id === $studyPath->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, StudyPath $studyPath): bool
    {
        return $user->id === $studyPath->user_id;
    }

    public function delete(User $user, StudyPath $studyPath): bool
    {
        return $user->id === $studyPath->user_id;
    }
}
