<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        try {
            DB::connection()->getPdo();
            $services['database'] = 'ok';
        } catch (\Throwable $e) {
            Log::warning('Health check: database failed', ['exception' => $e]);
            $services['database'] = 'error';
            $healthy = false;
        }

        try {
            Redis::ping();
            $services['redis'] = 'ok';
        } catch (\Throwable $e) {
            Log::warning('Health check: redis failed', ['exception' => $e]);
            $services['redis'] = 'error';
            $healthy = false;
        }

        try {
            $queueConn = config('queue.default');
            $services['queue'] = $queueConn === 'redis' ? 'ok' : $queueConn;
        } catch (\Throwable $e) {
            Log::warning('Health check: queue failed', ['exception' => $e]);
            $services['queue'] = 'error';
            $healthy = false;
        }

        $reverbHost = config('broadcasting.connections.reverb.options.host', 'localhost');
        $reverbPort = config('broadcasting.connections.reverb.options.port', 8080);
        try {
            $hostStr = is_string($reverbHost) ? $reverbHost : 'localhost';
            $portInt = (is_int($reverbPort) || is_string($reverbPort)) ? (int) $reverbPort : 8080;
            $socket = @fsockopen($hostStr, $portInt, $errno, $errstr, 2);
            $services['websocket'] = $socket ? 'ok' : 'error';
            if ($socket) {
                fclose($socket);
            }
        } catch (\Throwable $e) {
            Log::warning('Health check: websocket failed', ['exception' => $e, 'host' => $reverbHost, 'port' => $reverbPort]);
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

        return response()->json($responseData, $healthy ? 200 : 503);
    }
}
