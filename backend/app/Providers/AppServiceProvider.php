<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExceptionHandler::class, \App\Exceptions\Handler::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! app()->isProduction());

        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('sensitive', fn (Request $request) => Limit::perMinute(5)->by($request->user()?->id ?? $request->ip()));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?? $request->ip()));
        RateLimiter::for('recalculate', fn (Request $request) => Limit::perMinute(2)->by($request->user()?->id ?? $request->ip()));
        RateLimiter::for('export', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?? $request->ip()));
        RateLimiter::for('health', fn (Request $request) => Limit::perMinute(300)->by($request->ip()));
        $this->loadMigrationsFrom([
            database_path('migrations'),
            database_path('migrations/transactional'),
            database_path('migrations/analytics'),
        ]);

        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            \Laravel\Horizon\Horizon::auth(function ($request) {
                if (app()->environment('local')) {
                    return true;
                }

                $user = $request->user();
                if (! $user) {
                    return false;
                }

                $adminEmails = array_map('trim', explode(',', config('app.horizon_admin_emails', '')));

                return in_array($user->email, $adminEmails, true);
            });
        }
    }
}
