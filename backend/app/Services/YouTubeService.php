<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Serviço proxy para a YouTube Data API v3.
 *
 * Encapsula chamadas HTTP à API pública do YouTube, com cache simples em Redis
 * e log estruturado.
 */
class YouTubeService
{
    private const BASE_URL = 'https://www.googleapis.com/youtube/v3';

    /** Máximo de resultados por página. */
    private const MAX_RESULTS = 20;

    /** TTL do cache em segundos (1 hora). */
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly string $apiKey,
        private readonly ?string $cacheKey = null
    ) {}

    /**
     * Busca vídeos no YouTube.
     *
     * @param string $query       Termo de busca.
     * @param string $pageToken   Token para paginação.
     * @param int    $maxResults  Resultados por página (máx 50).
     * @return array{items: array, nextPageToken: string|null, prevPageToken: string|null, totalResults: int}
     *
     * @throws RequestException|ConnectionException
     */
    public function search(string $query, string $pageToken = '', int $maxResults = self::MAX_RESULTS): array
    {
        $maxResults = min(max($maxResults, 1), 50);

        $params = [
            'part'       => 'snippet',
            'type'       => 'video',
            'videoEmbeddable' => 'true',
            'maxResults' => $maxResults,
            'q'          => $query,
            'key'        => $this->apiKey,
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $cacheKey = $this->cacheKey("search:{$query}:{$pageToken}:{$maxResults}");

        return $this->cached($cacheKey, function () use ($params) {
            $response = Http::timeout(10)
                ->retry(2, 200)
                ->get(self::BASE_URL . '/search', $params);

            if ($response->failed()) {
                Log::warning('YouTube search failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                throw new RequestException($response);
            }

            $data = $response->json();

            Log::info('YouTube search', [
                'query'        => $params['q'],
                'totalResults' => $data['pageInfo']['totalResults'] ?? 0,
            ]);

            return [
                'items'         => $data['items'] ?? [],
                'nextPageToken' => $data['nextPageToken'] ?? null,
                'prevPageToken' => $data['prevPageToken'] ?? null,
                'totalResults'  => $data['pageInfo']['totalResults'] ?? 0,
            ];
        });
    }

    /**
     * Obtém detalhes de vídeos específicos (duração, estatísticas, etc.).
     *
     * @param string[]|string $ids ID(s) do vídeo.
     * @return array{items: array}
     *
     * @throws RequestException|ConnectionException
     */
    public function videos(array|string $ids): array
    {
        $idList = is_array($ids) ? implode(',', $ids) : $ids;
        $params = [
            'part' => 'snippet,contentDetails,statistics',
            'id'   => $idList,
            'key'  => $this->apiKey,
        ];

        $cacheKey = $this->cacheKey("videos:{$idList}");

        return $this->cached($cacheKey, function () use ($params) {
            $response = Http::timeout(10)
                ->retry(2, 200)
                ->get(self::BASE_URL . '/videos', $params);

            if ($response->failed()) {
                Log::warning('YouTube videos failed', [
                    'status' => $response->status(),
                ]);
                throw new RequestException($response);
            }

            $data = $response->json();

            return [
                'items' => $data['items'] ?? [],
            ];
        });
    }

    /**
     * Sanitiza e serializa a chave de cache.
     */
    private function cacheKey(string $suffix): string
    {
        $prefix = $this->cacheKey ?? 'youtube';
        return "{$prefix}:{$suffix}";
    }

    /**
     * Cache simples: se Redis disponível, usa; senão, executa direto.
     */
    private function cached(string $key, callable $fetch): array
    {
        try {
            if (function_exists('cache')) {
                return cache()->remember($key, self::CACHE_TTL, $fetch);
            }
        } catch (\Throwable) {
            // Redis indisponível — fallback sem cache
        }

        return $fetch();
    }
}
