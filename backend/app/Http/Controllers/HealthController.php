<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Controlador de health check da API.
 *
 * Verifica conectividade com: banco (PostgreSQL), Redis, fila e WebSocket (Reverb).
 * Usado por load balancers e monitoramento. Retorna 503 se algum serviço falhar.
 */
class HealthController extends Controller
{
    /**
     * Executa o health check e retorna status de cada dependência.
     */
    public function __invoke(): JsonResponse
    {
        $services = [];
        $healthy = true;
        $startedAt = microtime(true);

        try {
            $dbStartedAt = microtime(true);
            DB::connection()->getPdo();
            $services['database'] = 'ok';
            // #region agent log
            @file_put_contents(
                base_path('../debug-ba100a.log'),
                json_encode([
                    'sessionId' => 'ba100a',
                    'runId' => 'project-smoke',
                    'hypothesisId' => 'H6',
                    'location' => 'backend/app/Http/Controllers/HealthController.php',
                    'message' => 'health_database_check',
                    'data' => ['duration_ms' => round((microtime(true) - $dbStartedAt) * 1000, 2)],
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_SLASHES).PHP_EOL,
                FILE_APPEND
            );
            // #endregion
        } catch (\Throwable) {
            $services['database'] = 'error';
            $healthy = false;
        }

        try {
            $redisStartedAt = microtime(true);
            Redis::ping();
            $services['redis'] = 'ok';
            // #region agent log
            @file_put_contents(
                base_path('../debug-ba100a.log'),
                json_encode([
                    'sessionId' => 'ba100a',
                    'runId' => 'project-smoke',
                    'hypothesisId' => 'H6',
                    'location' => 'backend/app/Http/Controllers/HealthController.php',
                    'message' => 'health_redis_check',
                    'data' => ['duration_ms' => round((microtime(true) - $redisStartedAt) * 1000, 2)],
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_SLASHES).PHP_EOL,
                FILE_APPEND
            );
            // #endregion
        } catch (\Throwable) {
            $services['redis'] = 'error';
            $healthy = false;
        }

        try {
            $queueConn = config('queue.default');
            $services['queue'] = $queueConn === 'redis' ? 'ok' : $queueConn;
        } catch (\Throwable) {
            $services['queue'] = 'error';
        }

        $reverbHost = config('broadcasting.connections.reverb.options.host', 'localhost');
        $reverbPort = config('broadcasting.connections.reverb.options.port', 8080);
        try {
            $wsStartedAt = microtime(true);
            $socket = @fsockopen($reverbHost, (int) $reverbPort, $errno, $errstr, 2);
            $services['websocket'] = $socket ? 'ok' : 'error';
            if ($socket) {
                fclose($socket);
            }
            // #region agent log
            @file_put_contents(
                base_path('../debug-ba100a.log'),
                json_encode([
                    'sessionId' => 'ba100a',
                    'runId' => 'project-smoke',
                    'hypothesisId' => 'H6',
                    'location' => 'backend/app/Http/Controllers/HealthController.php',
                    'message' => 'health_websocket_check',
                    'data' => [
                        'host' => $reverbHost,
                        'port' => (int) $reverbPort,
                        'status' => $services['websocket'],
                        'duration_ms' => round((microtime(true) - $wsStartedAt) * 1000, 2),
                    ],
                    'timestamp' => (int) round(microtime(true) * 1000),
                ], JSON_UNESCAPED_SLASHES).PHP_EOL,
                FILE_APPEND
            );
            // #endregion
        } catch (\Throwable) {
            $services['websocket'] = 'error';
        }

        $responseData = [
            'status' => $healthy ? 'healthy' : 'degraded',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ];

        if (app()->environment('local', 'testing')) {
            $responseData['services'] = $services;
        }

        // #region agent log
        @file_put_contents(
            base_path('../debug-ba100a.log'),
            json_encode([
                'sessionId' => 'ba100a',
                'runId' => 'project-smoke',
                'hypothesisId' => 'H6',
                'location' => 'backend/app/Http/Controllers/HealthController.php',
                'message' => 'health_response',
                'data' => [
                    'healthy' => $healthy,
                    'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                ],
                'timestamp' => (int) round(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES).PHP_EOL,
            FILE_APPEND
        );
        // #endregion

        return response()->json($responseData, $healthy ? 200 : 503);
    }
}
