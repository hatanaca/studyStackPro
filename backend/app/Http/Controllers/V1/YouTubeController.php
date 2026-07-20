<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\YouTube\YouTubeSearchRequest;
use App\Http\Requests\YouTube\YouTubeVideosRequest;
use App\Services\YouTubeService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Proxy autenticado para a YouTube Data API v3.
 *
 * Todas as rotas requerem auth:sanctum — os vídeos são carregados
 * no contexto do usuário logado (sem expor a API key ao frontend).
 */
class YouTubeController extends Controller
{
    use HasApiResponse;

    public function __construct(private YouTubeService $youtube) {}

    /**
     * GET /api/v1/youtube/search?q=...&pageToken=...&maxResults=...
     */
    public function search(YouTubeSearchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->youtube->search(
                $validated['q'],
                $validated['pageToken'] ?? '',
                $validated['maxResults'] ?? 20
            );

            return $this->success($result);
        } catch (\Throwable $e) {
            Log::error('YouTube search error', ['exception' => $e, 'query' => $validated['q'] ?? null]);

            return $this->error('Falha ao buscar vídeos. Tente novamente.', 'YOUTUBE_ERROR', null, 502);
        }
    }

    /**
     * GET /api/v1/youtube/videos?ids=id1,id2,...
     */
    public function videos(YouTubeVideosRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $rawIds = array_slice(
            array_filter(explode(',', $validated['ids'])),
            0,
            50
        );

        $ids = array_filter($rawIds, fn (string $id) => preg_match('/^[a-zA-Z0-9_-]{11}$/', $id));

        if (empty($ids)) {
            return $this->error('Informe ao menos um ID de vídeo válido.', 'VALIDATION_ERROR', null, 422);
        }

        try {
            $result = $this->youtube->videos($ids);

            return $this->success($result);
        } catch (\Throwable $e) {
            Log::error('YouTube videos error', ['exception' => $e, 'ids' => $ids]);

            return $this->error('Falha ao buscar detalhes dos vídeos.', 'YOUTUBE_ERROR', null, 502);
        }
    }

    /**
     * GET /api/v1/youtube/playlists
     *
     * Busca playlists do usuário autenticado via OAuth Google.
     */
    public function playlists(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->google_token) {
            return $this->error('Conta Google não vinculada. Faça login com Google.', 'UNAUTHENTICATED', null, 401);
        }

        try {
            $result = $this->youtube->playlists($user->google_token);

            return $this->success($result);
        } catch (\Throwable $e) {
            Log::error('YouTube playlists error', ['exception' => $e, 'user_id' => $user->id]);

            return $this->error('Falha ao buscar playlists. Token pode ter expirado. Faça login novamente.', 'YOUTUBE_ERROR', null, 502);
        }
    }
}
