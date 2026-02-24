<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleJson
{
    public function handle(Request $request, Closure $next, string $limit = '60'): Response
    {
        $key = $request->user()?->id ?? $request->ip();

        if (RateLimiter::tooManyAttempts('api:' . $key, (int) $limit)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMITED',
                    'message' => 'Muitas requisições. Tente novamente em alguns segundos.',
                ],
            ], 429);
        }

        RateLimiter::hit('api:' . $key);

        return $next($request);
    }
}
