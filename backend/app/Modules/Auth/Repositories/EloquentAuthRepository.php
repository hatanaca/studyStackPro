<?php

namespace App\Modules\Auth\Repositories;

use App\Models\User;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;

/**
 * Implementação Eloquent do repositório de autenticação.
 * Persistência de usuários (create, findByEmail, updatePassword).
 */
class EloquentAuthRepository implements AuthRepositoryInterface
{
    /** Cria usuário no banco */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /** Busca usuário por email (único) */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /** Atualiza senha do usuário (já hasheada) */
    public function updatePassword(User $user, string $hashedPassword): bool
    {
        $user->password = $hashedPassword;

        return $user->save();
    }
}
