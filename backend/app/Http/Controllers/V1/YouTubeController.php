<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\YouTubeService;
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
    public function __construct(private YouTubeService $youtube) {}

    /**
     * GET /api/v1/youtube/search?q=...&pageToken=...&maxResults=...
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|max:200',
            'pageToken' => 'string|nullable',
            'maxResults' => 'integer|min:1|max:50|nullable',
        ]);

        try {
            $result = $this->youtube->search(
                $validated['q'],
                $validated['pageToken'] ?? '',
                $validated['maxResults'] ?? 20
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('YouTube search error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => ['message' => 'Falha ao buscar vídeos. Tente novamente.'],
            ], 502);
        }
    }

    /**
     * GET /api/v1/youtube/videos?ids=id1,id2,...
     */
    public function videos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|string|max:500',
        ]);

        $ids = array_slice(
            array_filter(explode(',', $validated['ids'])),
            0,
            50
        );

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Informe ao menos um ID de vídeo.'],
            ], 422);
        }

        try {
            $result = $this->youtube->videos($ids);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('YouTube videos error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => ['message' => 'Falha ao buscar detalhes dos vídeos.'],
            ], 502);
        }
    }

    /**
     * GET /api/v1/youtube/playlists
     *
     * Busca playlists do usuário autenticado via OAuth Google.
     */
    public function playlists(): JsonResponse
    {
        $user = auth()->user();

        if (! $user || ! $user->google_token) {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Conta Google não vinculada. Faça login com Google.'],
            ], 401);
        }

        try {
            $result = $this->youtube->playlists($user->google_token);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('YouTube playlists error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => ['message' => 'Falha ao buscar playlists. Token pode ter expirado. Faça login novamente.'],
            ], 502);
        }
    }
}
