<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LinkedIn\SharePostRequest;
use App\Modules\LinkedIn\DTOs\LinkedInPostDTO;
use App\Modules\LinkedIn\Services\LinkedInService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gerenciar integração com LinkedIn.
 *
 * Rotas: status, share, disconnect.
 */
class LinkedInController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly LinkedInService $linkedin,
    ) {}

    /**
     * GET /api/v1/linkedin/status
     *
     * Retorna se o usuário tem LinkedIn conectado e dados do perfil.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->linkedin->isConnected($user)) {
            return $this->success(['connected' => false]);
        }

        try {
            $profile = $this->linkedin->getProfile($user);

            return $this->success([
                'connected' => true,
                'profile' => [
                    'id' => $profile['id'] ?? null,
                    'name' => trim(($profile['localizedFirstName'] ?? '').' '.($profile['localizedLastName'] ?? '')),
                    'headline' => $profile['headline'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('LinkedIn profile fetch failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return $this->success(['connected' => true, 'profile' => null]);
        }
    }

    /**
     * POST /api/v1/linkedin/share
     *
     * Publica um post no LinkedIn.
     */
    public function share(SharePostRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->linkedin->isConnected($user)) {
            return $this->error(
                'Conta LinkedIn não conectada. Faça login com LinkedIn.',
                'LINKEDIN_NOT_CONNECTED',
                null,
                403
            );
        }

        $dto = new LinkedInPostDTO(text: $request->validated('text'));

        try {
            $result = $this->linkedin->sharePost($user, $dto);

            return $this->success($result, 'Post publicado no LinkedIn com sucesso.');
        } catch (\Throwable $e) {
            Log::error('LinkedIn share failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                'Falha ao publicar no LinkedIn. O token pode ter expirado.',
                'LINKEDIN_API_ERROR',
                null,
                502
            );
        }
    }

    /**
     * POST /api/v1/linkedin/disconnect
     *
     * Remove a vinculação da conta LinkedIn.
     */
    public function disconnect(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'linkedin_id' => null,
            'linkedin_token' => null,
            'linkedin_refresh_token' => null,
            'linkedin_token_expires_at' => null,
        ]);

        return $this->success(null, 'Conta LinkedIn desconectada.');
    }
}
