<?php

namespace App\Providers;

use App\Exceptions\Handler;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;
use Sentry\Laravel\Integration;
use Sentry\State\Scope;
use SocialiteProviders\Discord\DiscordExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExceptionHandler::class, Handler::class);
    }

    public function boot(): void
    {
        $appUrl = config('app.url');
        if (is_string($appUrl) && str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }

        Model::shouldBeStrict(! app()->isProduction());

        // Sentry context: environment info attached to every event
        if (app()->bound('sentry')) {
            Integration::configureScope(function (Scope $scope): void {
                $scope->setContext('app', [
                    'name' => config('app.name'),
                    'env' => config('app.env'),
                ]);
            });
        }

        // Registra o driver OAuth do Discord (SocialiteProviders)
        Event::listen(SocialiteWasCalled::class, DiscordExtendSocialite::class);

        // Authentication endpoints - strict rate limiting to prevent brute force
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(3)->by($request->ip().':'.$request->input('email', '')));
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        // Sensitive operations - strict rate limiting
        RateLimiter::for('sensitive', fn (Request $request) => Limit::perMinute(5)->by($request->user()?->id ?? $request->ip()));

        // User operations - moderate rate limiting
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?? $request->ip()));
        RateLimiter::for('recalculate', fn (Request $request) => Limit::perMinute(2)->by($request->user()?->id ?? $request->ip()));
        RateLimiter::for('export', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?? $request->ip()));

        // Health check - high limit for monitoring
        RateLimiter::for('health', fn (Request $request) => Limit::perMinute(300)->by($request->ip()));

        $this->loadMigrationsFrom([
            database_path('migrations'),
            database_path('migrations/transactional'),
            database_path('migrations/analytics'),
        ]);

        if (class_exists(Horizon::class)) {
            Horizon::auth(function ($request) {
                $allowedIps = array_filter(array_map('trim', explode(',', (string) config('app.horizon_allowed_ips', ''))));
                if ($allowedIps === [] || ! in_array($request->ip(), $allowedIps, true)) {
                    return false;
                }

                $user = $request->user();
                if (! $user) {
                    return false;
                }

                $adminEmails = array_map('trim', explode(',', config('app.horizon_admin_emails', '')));

                return in_array($user->email, array_filter($adminEmails), true);
            });
        }
    }
}
