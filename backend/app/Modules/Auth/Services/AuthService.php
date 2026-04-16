<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Serviço de autenticação.
 *
 * Centraliza lógica de registro, login, troca de senha. Usa AuthRepository para persistência
 * e Sanctum para tokens. Login valida credenciais sem sessão web (API stateless), para o
 * guard Sanctum não autenticar requisições seguintes só pela sessão.
 */
class AuthService
{
    /**
     * Injeta o repositório de auth para abstrair persistência.
     */
    public function __construct(
        private AuthRepositoryInterface $authRepository,
        private TokenService $tokenService
    ) {}

    /**
     * Cria um novo usuário com senha hasheada.
     */
    public function register(RegisterDTO $dto): User
    {
        $passwordHash = Hash::make($dto->password);

        return $this->authRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $passwordHash,
            'timezone' => $dto->timezone,
        ]);
    }

    /**
     * Valida email/senha e retorna user + token. Revoga tokens anteriores. Não usa Auth::attempt
     * (evita sessão web; o Guard do Sanctum consulta o guard "web" antes do Bearer).
     *
     * @return array{user: User, token: string}|null null se credenciais inválidas
     */
    public function login(LoginDTO $dto): ?array
    {
        $user = $this->authRepository->findByEmail($dto->email);
        if ($user === null || ! Hash::check($dto->password, $user->getAuthPassword())) {
            return null;
        }

        $this->tokenService->revokeMany($user->tokens()->get());
        $token = $user->createToken('api-token')->plainTextToken;

        return ['user' => $user->fresh(), 'token' => $token];
    }

    /**
     * Atualiza dados do perfil do usuário (nome, email, timezone, etc.).
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        if (array_key_exists('timezone', $data)) {
            Cache::forget("user_timezone:{$user->id}");
        }

        return $user->fresh();
    }

    /**
     * Altera a senha do usuário. Revoga todos os tokens em caso de sucesso.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->getAuthPassword())) {
            return false;
        }

        // Revogar antes de persistir a nova senha: o save() pode afetar relação/cache de tokens em memória.
        $this->tokenService->revokeMany($user->tokens()->get());

        return $this->authRepository->updatePassword($user, Hash::make($newPassword));
    }
}
