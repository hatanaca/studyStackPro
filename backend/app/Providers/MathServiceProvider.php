<?php

namespace App\Providers;

use App\Services\MathService;
use Illuminate\Support\ServiceProvider;

class MathServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MathService::class, fn () => new MathService(
            baseUrl: (string) config('services.math.url'),
            token: (string) config('services.math.token'),
        ));
    }
}
