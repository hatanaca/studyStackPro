<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        private AuthRepositoryInterface $authRepository,
        private TokenService $tokenService
    ) {}

    /**
     * Cria um novo usuário com senha hasheada.
     */
    public function register(RegisterDTO $dto): User
    {
        $startedAt = microtime(true);

        // #region agent log
        @file_put_contents(
            base_path('../debug-ba100a.log'),
            json_encode([
                'sessionId' => 'ba100a',
                'runId' => 'project-smoke',
                'hypothesisId' => 'H7',
                'location' => 'backend/app/Modules/Auth/Services/AuthService.php',
                'message' => 'register_start',
                'data' => ['hasTimezone' => $dto->timezone !== null],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND
        );
        // #endregion

        $hashStartedAt = microtime(true);
        $passwordHash = Hash::make($dto->password);

        // #region agent log
        @file_put_contents(
            base_path('../debug-ba100a.log'),
            json_encode([
                'sessionId' => 'ba100a',
                'runId' => 'project-smoke',
                'hypothesisId' => 'H7',
                'location' => 'backend/app/Modules/Auth/Services/AuthService.php',
                'message' => 'register_hash_complete',
                'data' => ['duration_ms' => round((microtime(true) - $hashStartedAt) * 1000, 2)],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND
        );
        // #endregion

        $createStartedAt = microtime(true);
        $user = $this->authRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $passwordHash,
            'timezone' => $dto->timezone,
        ]);

        // #region agent log
        @file_put_contents(
            base_path('../debug-ba100a.log'),
            json_encode([
                'sessionId' => 'ba100a',
                'runId' => 'project-smoke',
                'hypothesisId' => 'H7',
                'location' => 'backend/app/Modules/Auth/Services/AuthService.php',
                'message' => 'register_create_complete',
                'data' => [
                    'duration_ms' => round((microtime(true) - $createStartedAt) * 1000, 2),
                    'total_duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND
        );
        // #endregion

        return $user;
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
        $this->tokenService->revokeMany($user->tokens()->get());
        $token = $user->createToken('api-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
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
        if (! Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $updated = $this->authRepository->updatePassword($user, Hash::make($newPassword));
        if ($updated) {
            $this->tokenService->revokeMany($user->tokens()->get());
        }

        return $updated;
    }
}
