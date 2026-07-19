<?php

namespace App\Http\Middleware;

use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetUserTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->timezone && $this->isValidTimezone($user->timezone)) {
            // Armazenar timezone no request para uso local (não mutar Carbon global)
            $request->attributes->set('user_timezone', $user->timezone);
        }

        return $next($request);
    }

    private function isValidTimezone(string $tz): bool
    {
        return in_array($tz, DateTimeZone::listIdentifiers(), true);
    }
}
