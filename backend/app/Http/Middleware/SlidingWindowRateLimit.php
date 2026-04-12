<?php

namespace App\Http\Middleware;

use App\Services\RedisLuaService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SlidingWindowRateLimit
{
    public function __construct(
        private RedisLuaService $redisLuaService
    ) {}

    public function handle(Request $request, Closure $next, int|string $limit = 60): Response
    {
        $limit = max((int) $limit, 1);
        $userKey = $request->user()?->id ?? $request->ip();
        $path = trim($request->path(), '/');
        $now = (int) round(microtime(true) * 1000);

        try {
            $result = $this->redisLuaService->callScript('sliding_window', [
                "rate:{$userKey}:{$path}",
            ], [
                $now,
                60000,
                $limit,
            ]);

            [$allowed, $retryAfter] = $this->normalizeResult($result);

            // #region agent log
            @file_put_contents(
                base_path('../debug-ba100a.log'),
                json_encode([
                    'sessionId' => 'ba100a',
                    'runId' => 'project-smoke',
                    'hypothesisId' => 'H4',
                    'location' => 'backend/app/Http/Middleware/SlidingWindowRateLimit.php',
                    'message' => 'rate_limit_evaluated',
                    'data' => [
                        'path' => $path,
                        'limit' => $limit,
                        'allowed' => $allowed,
                        'retryAfter' => $retryAfter,
                    ],
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_SLASHES).PHP_EOL,
                FILE_APPEND
            );
            // #endregion

            if ($allowed !== 1) {
                return $this->buildBlockedResponse($limit, $retryAfter);
            }
        } catch (Throwable $exception) {
            // #region agent log
            @file_put_contents(
                base_path('../debug-ba100a.log'),
                json_encode([
                    'sessionId' => 'ba100a',
                    'runId' => 'project-smoke',
                    'hypothesisId' => 'H4',
                    'location' => 'backend/app/Http/Middleware/SlidingWindowRateLimit.php',
                    'message' => 'rate_limit_exception',
                    'data' => [
                        'path' => $path,
                        'limit' => $limit,
                        'error' => $exception->getMessage(),
                        'failOpen' => (bool) config('services.rate_limit.fail_open', false),
                    ],
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_SLASHES).PHP_EOL,
                FILE_APPEND
            );
            // #endregion
            Log::warning('Sliding window Lua indisponível.', [
                'path' => $path,
                'user_key' => $userKey,
                'error' => $exception->getMessage(),
            ]);

            if (! config('services.rate_limit.fail_open', false)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SERVICE_UNAVAILABLE',
                        'message' => 'Serviço temporariamente indisponível.',
                    ],
                ], 503);
            }
        }

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $limit);

        return $response;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function normalizeResult(mixed $result): array
    {
        $values = array_values(is_array($result) ? $result : [$result]);
        $allowed = (int) ($values[0] ?? 1);
        $retryAfter = (int) ($values[1] ?? 0);

        return [$allowed, max($retryAfter, 0)];
    }

    private function buildBlockedResponse(int $limit, int $retryAfter): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMITED',
                'message' => 'Limite de requisições excedido. Tente novamente em instantes.',
            ],
        ], 429, [
            'Retry-After' => (string) max($retryAfter, 1),
            'X-RateLimit-Limit' => (string) $limit,
        ]);
    }
}
