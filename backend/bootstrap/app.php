<?php

if (! defined('SIGINT')) {
    define('SIGINT', 2);
}
if (! defined('SIGTERM')) {
    define('SIGTERM', 15);
}
if (! defined('SIGTSTP')) {
    define('SIGTSTP', 20);
}
use App\Http\Middleware\EnsureJsonResponse;
use App\Http\Middleware\LogApiRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetUserTimezone;
use App\Http\Middleware\SlidingWindowRateLimit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use SocialiteProviders\Manager\ServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withProviders([ServiceProvider::class])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['throttle.sliding' => SlidingWindowRateLimit::class]);
        $middleware->statefulApi();
        // API routes usam Bearer tokens ou Sanctum SPA (cookie), não CSRF de formulário.
        $middleware->validateCsrfTokens(except: ['api/*', 'sanctum/*']);
        $trustedProxies = env('TRUSTED_PROXIES');
        if (is_string($trustedProxies) && trim($trustedProxies) !== '') {
            $trimmed = trim($trustedProxies);
            if ($trimmed === '*') {
                $middleware->trustProxies(at: '*');
            } else {
                $at = array_values(array_filter(array_map('trim', explode(',', $trustedProxies))));
                if ($at !== []) {
                    $middleware->trustProxies(at: $at);
                }
            }
        }
        $middleware->append(SecurityHeaders::class);
        $middleware->api(prepend: [EnsureJsonResponse::class]);
        $middleware->api(append: [SetUserTimezone::class, LogApiRequests::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->expectsJson());
    })->create();
