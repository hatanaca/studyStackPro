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
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private AuthService $authService
    ) {}

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

        return $this->success(new UserResource($user), 'Registrado com sucesso.', 201)->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ]);
    }

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

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Token revogado com sucesso.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return $this->success(new UserResource($user->fresh()), 'Perfil atualizado.');
    }

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

        return $this->success(null, 'Senha alterada com sucesso.');
    }

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

    public function revokeAllTokens(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->success(null, 'Todos os tokens foram revogados.');
    }
}
