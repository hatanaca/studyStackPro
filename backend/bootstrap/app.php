<?php

// Fallback para constantes de sinal quando pcntl não está disponível (ex: ambientes Docker sem pcntl)
// Valores POSIX padrão Linux: SIGINT=2, SIGTERM=15, SIGTSTP=20 (macOS também usa 20 via POSIX)
if (! defined('SIGINT')) {
    define('SIGINT', 2);
}
if (! defined('SIGTERM')) {
    define('SIGTERM', 15);
}
if (! defined('SIGTSTP')) {
    define('SIGTSTP', 20);
}

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'throttle.sliding' => \App\Http\Middleware\SlidingWindowRateLimit::class,
        ]);

        $middleware->statefulApi();

        if (env('APP_ENV') === 'testing') {
            $middleware->validateCsrfTokens(except: [
                'api/*',
                'sanctum/*',
            ]);
        }

        // Atrás de Nginx / load balancer: necessário para URL HTTPS, rate limit por IP real e cookies seguros.
        $trusted = env('TRUSTED_PROXIES');
        if (is_string($trusted) && trim($trusted) !== '') {
            $trimmed = trim($trusted);
            if ($trimmed === '*') {
                $middleware->trustProxies(at: '*');
            } else {
                $at = array_values(array_filter(array_map('trim', explode(',', $trusted))));
                if ($at !== []) {
                    $middleware->trustProxies(at: $at);
                }
            }
        }

        $middleware->api(prepend: [
            \App\Http\Middleware\EnsureJsonResponse::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\SetUserTimezone::class,
            \App\Http\Middleware\LogApiRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->expectsJson());
    })->create();
