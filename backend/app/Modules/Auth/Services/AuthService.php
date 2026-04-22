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
 * e Sanctum. Login/registo criam sessão web (cookie HttpOnly) para a SPA; tokens pessoais
 * (Bearer) continuam disponíveis para testes e integrações que os criem explicitamente.
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
     * Valida email/senha, revoga tokens pessoais antigos e devolve o utilizador.
     * A sessão web (cookie) é iniciada no controlador após este retorno.
     *
     * @return array{user: User}|null null se credenciais inválidas
     */
    public function login(LoginDTO $dto): ?array
    {
        $user = $this->authRepository->findByEmail($dto->email);
        if ($user === null || ! Hash::check($dto->password, $user->getAuthPassword())) {
            return null;
        }

        $this->tokenService->revokeMany($user->tokens()->get());

        return ['user' => $user->fresh()];
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
