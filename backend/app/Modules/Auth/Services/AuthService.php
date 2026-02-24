<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(
        private AuthRepositoryInterface $authRepository
    ) {}

    public function register(string $name, string $email, string $password, string $timezone = 'UTC'): User
    {
        return $this->authRepository->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'timezone' => $timezone,
        ]);
    }

    /**
     * @return array{user: User, token: string}|null
     */
    public function login(string $email, string $password, bool $remember = false): ?array
    {
        if (! Auth::attempt(compact('email', 'password'))) {
            return null;
        }
        $user = Auth::user();
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            return false;
        }

        return $this->authRepository->updatePassword($user, Hash::make($newPassword));
    }
}
