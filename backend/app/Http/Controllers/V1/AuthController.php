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

/**
 * Controlador de autenticação e gerenciamento de usuário.
 *
 * Responsável por: registro, login, logout, perfil, troca de senha e gestão de tokens
 * (Sanctum). Todas as rotas exigem middleware de autenticação, exceto register/login.
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
     * Registra um novo usuário no sistema.
     * Cria o usuário via AuthService, gera token Sanctum e retorna user + token no body (consistente com login).
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
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Registrado com sucesso.', 201);
    }

    /**
     * Autentica o usuário com email e senha.
     * Retorna user + token em caso de sucesso; 401 se credenciais inválidas.
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

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Revoga o token atual (logout). O token em uso é removido da base.
     */
    public function logout(Request $request): JsonResponse
    {
        $currentToken = $request->user()->currentAccessToken();

        if ($currentToken) {
            $this->tokenService->revoke($currentToken);
        }

        return $this->success(null, 'Token revogado com sucesso.');
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
