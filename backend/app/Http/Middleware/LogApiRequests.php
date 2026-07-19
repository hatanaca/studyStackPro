<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('_request_start', microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            $start = $request->attributes->get('_request_start', microtime(true));
            // Nunca logar $request->all() — contém dados sensíveis (senhas, tokens)
            Log::info('API Request', [
                'method' => $request->method(),
                'path' => $request->path(),
                'user_id' => $request->user()?->id,
                'status' => $response->getStatusCode(),
                'duration_ms' => round((microtime(true) - $start) * 1000, 2),
            ]);
        } catch (\Throwable $e) {
            // Falha ao logar NUNCA deve quebrar a requisição.
            // Erros comuns: permissão de arquivo de log, disco cheio.
            // Log via erro padrão como último recurso.
            error_log('LogApiRequests: '.$e->getMessage());
        }
    }
}
