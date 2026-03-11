<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Middleware que define app.timezone conforme timezone do usuário autenticado. */
class SetUserTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->timezone) {
            config(['app.timezone' => $request->user()->timezone]);
        }

        return $next($request);
    }
}
