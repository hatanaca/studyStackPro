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

    /**
     * Logs after the response has been sent to the client,
     * avoiding I/O overhead on the critical path.
     */
    public function terminate(Request $request, Response $response): void
    {
        $start = $request->attributes->get('_request_start', microtime(true));

        Log::channel('single')->info('API Request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => $request->user()?->id,
            'status' => $response->getStatusCode(),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);
    }
}
