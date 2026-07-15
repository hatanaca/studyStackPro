<?php

namespace App\Http\Middleware;

use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que define a timezone do usuário no request.
 *
 * NOTA: Não muta config('app.timezone') para evitar race conditions em requests concorrentes.
 * Em vez disso, define a timezone do Carbon para a duração do request.
 */
class SetUserTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->timezone && $this->isValidTimezone($user->timezone)) {
            Carbon::setTimezone($user->timezone);
        }

        return $next($request);
    }

    private function isValidTimezone(string $tz): bool
    {
        return in_array($tz, DateTimeZone::listIdentifiers(), true);
    }
}
