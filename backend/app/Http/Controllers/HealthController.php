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

        try {
            DB::connection()->getPdo();
            $services['database'] = 'ok';
        } catch (\Throwable) {
            $services['database'] = 'error';
            $healthy = false;
        }

        try {
            Redis::ping();
            $services['redis'] = 'ok';
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
            $socket = @fsockopen($reverbHost, (int) $reverbPort, $errno, $errstr, 2);
            $services['websocket'] = $socket ? 'ok' : 'error';
            if ($socket) {
                fclose($socket);
            }
        } catch (\Throwable) {
            $services['websocket'] = 'error';
        }

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'services' => $services,
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
