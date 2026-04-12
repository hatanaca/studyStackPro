<?php

namespace App\Http\Middleware;

use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Middleware que define app.timezone conforme timezone do usuário autenticado. */
class SetUserTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->timezone && $this->isValidTimezone($user->timezone)) {
            config(['app.timezone' => $user->timezone]);
        }

        return $next($request);
    }

    private function isValidTimezone(string $tz): bool
    {
        return in_array($tz, DateTimeZone::listIdentifiers(), true);
    }
}
