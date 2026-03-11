<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Serviço de autenticação.
 *
 * Centraliza lógica de registro, login, troca de senha. Usa AuthRepository para persistência
 * e Sanctum para tokens. No login, remove tokens anteriores (sessão única por vez).
 */
class AuthService
{
    /**
     * Injeta o repositório de auth para abstrair persistência.
     */
    public function __construct(
        private AuthRepositoryInterface $authRepository
    ) {}

    /**
     * Cria um novo usuário com senha hasheada.
     */
    public function register(RegisterDTO $dto): User
    {
        return $this->authRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'timezone' => $dto->timezone,
        ]);
    }

    /**
     * Autentica via Laravel Auth e retorna user + token. Remove tokens antigos.
     *
     * @return array{user: User, token: string}|null null se credenciais inválidas
     */
    public function login(LoginDTO $dto): ?array
    {
        if (! Auth::attempt(['email' => $dto->email, 'password' => $dto->password])) {
            return null;
        }
        $user = Auth::user();
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Altera a senha do usuário. Revoga todos os tokens em caso de sucesso.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $updated = $this->authRepository->updatePassword($user, Hash::make($newPassword));
        if ($updated) {
            $user->tokens()->delete();
        }

        return $updated;
    }
}
