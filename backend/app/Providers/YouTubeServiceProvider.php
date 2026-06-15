<?php

namespace App\Providers;

use App\Services\YouTubeService;
use Illuminate\Support\ServiceProvider;

class YouTubeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(YouTubeService::class, function () {
            $apiKey = config('services.youtube.api_key');

            if (empty($apiKey)) {
                throw new \RuntimeException('YOUTUBE_API_KEY não configurada.');
            }

            return new YouTubeService($apiKey);
        });
    }
}
