<?php

namespace App\Providers;

use Illuminate\Contracts\Foundation\ExceptionHandler;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExceptionHandler::class, \App\Exceptions\Handler::class);
    }

    public function boot(): void
    {
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
