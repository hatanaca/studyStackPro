<?php

namespace App\Modules\Auth\Repositories;

use App\Models\User;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;

class EloquentAuthRepository implements AuthRepositoryInterface
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function updatePassword(User $user, string $hashedPassword): bool
    {
        $user->password = $hashedPassword;

        return $user->save();
    }
}
