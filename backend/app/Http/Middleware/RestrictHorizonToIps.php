<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictHorizonToIps
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = array_filter(
            array_map('trim', explode(',', config('app.horizon_allowed_ips', '')))
        );

        if ($allowedIps === []) {
            return $next($request);
        }

        $clientIp = $request->ip();

        if (!in_array($clientIp, $allowedIps, true)) {
            abort(403, ' Horizon access denied.');
        }

        return $next($request);
    }
}
