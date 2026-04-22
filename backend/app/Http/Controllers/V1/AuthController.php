<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\TokenService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador de autenticação e gerenciamento de usuário.
 *
 * Responsável por: registro, login, logout, perfil, troca de senha e gestão de tokens
 * (Sanctum: sessão web para SPA + Bearer opcional). Todas as rotas exigem auth, exceto register/login.
 */
class AuthController extends Controller
{
    use HasApiResponse;

    /**
     * Injeta o AuthService para centralizar a lógica de autenticação.
     */
    public function __construct(
        private AuthService $authService,
        private TokenService $tokenService
    ) {}

    /**
     * Registra um novo utilizador, inicia sessão web (cookie HttpOnly) e devolve o perfil.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = new RegisterDTO(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            timezone: $request->validated('timezone', 'UTC')
        );
        $user = $this->authService->register($dto);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $this->success([
            'user' => new UserResource($user->fresh()),
        ], 'Registrado com sucesso.', 201);
    }

    /**
     * Autentica com email/senha, inicia sessão web (cookie HttpOnly) e devolve o perfil.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $dto = new LoginDTO(
            email: $request->validated('email'),
            password: $request->validated('password'),
            remember: $request->boolean('remember')
        );
        $result = $this->authService->login($dto);
        if (! $result) {
            return $this->error('Credenciais inválidas.', 'UNAUTHENTICATED', null, 401);
        }

        $user = $result['user'];
        Auth::guard('web')->login($user, $dto->remember);
        $request->session()->regenerate();

        return $this->success([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Termina sessão web e, se aplicável, revoga o token Bearer atual (PAT).
     */
    public function logout(Request $request): JsonResponse
    {
        $currentToken = $request->user()?->currentAccessToken();
        if ($currentToken !== null) {
            $this->tokenService->revoke($currentToken);
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->success(null, 'Sessão terminada.');
    }

    /**
     * Retorna o usuário autenticado atual (perfil resumido).
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    /**
     * Atualiza dados do perfil (nome, email, timezone). Validação via UpdateProfileRequest.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());

        return $this->success(new UserResource($user), 'Perfil atualizado.');
    }

    /**
     * Altera a senha do usuário. Requer senha atual para validação.
     * Retorna 422 se a senha atual estiver incorreta.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $this->authService->changePassword(
            $user,
            $request->validated('current_password'),
            $request->validated('password')
        )) {
            return $this->error('Senha atual incorreta.', 'VALIDATION_ERROR', null, 422);
        }

        return $this->success(null, 'Senha alterada. Reconecte seus dispositivos.');
    }

    /**
     * Lista todos os tokens de acesso do usuário (útil para gestão de sessões/dispositivos).
     */
    public function tokens(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->get(['id', 'name', 'created_at', 'last_used_at']);

        return $this->success($tokens->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'created_at' => $t->created_at?->toIso8601String(),
            'last_used_at' => $t->last_used_at?->toIso8601String(),
        ]));
    }

    /**
     * Revoga todos os tokens do usuário (logout de todos os dispositivos).
     * Retorna quantos tokens foram revogados.
     */
    public function revokeAllTokens(Request $request): JsonResponse
    {
        $count = $this->tokenService->revokeMany($request->user()->tokens()->get());

        return $this->success(
            ['revoked_count' => $count],
            $count === 1
                ? '1 token revogado.'
                : "{$count} tokens revogados."
        );
    }
}
