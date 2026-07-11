<?php

use App\Providers\AppServiceProvider;
use App\Providers\YouTubeServiceProvider;
use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Broadcasting\BroadcastServiceProvider;
use Illuminate\Bus\BusServiceProvider;
use Illuminate\Cache\CacheServiceProvider;
use Illuminate\Cookie\CookieServiceProvider;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Encryption\EncryptionServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Providers\ConsoleSupportServiceProvider;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Illuminate\Hashing\HashServiceProvider;
use Illuminate\Queue\QueueServiceProvider;
use Illuminate\Redis\RedisServiceProvider;
use Illuminate\Session\SessionServiceProvider;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\TranslationServiceProvider;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\ViewServiceProvider;
use Laravel\Horizon\HorizonServiceProvider;
use Laravel\Reverb\ReverbServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;

return [
    'name' => env('APP_NAME', 'StudyTrackPro'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'UTC',
    'locale' => 'pt_BR',
    'fallback_locale' => 'en',
    'faker_locale' => 'pt_BR',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'maintenance' => [
        'driver' => 'file',
    ],
    'horizon_admin_emails' => env('HORIZON_ADMIN_EMAILS', ''),
    'horizon_allowed_ips' => env('HORIZON_ALLOWED_IPS', ''),
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    'providers' => [
        AuthServiceProvider::class,
        BroadcastServiceProvider::class,
        CookieServiceProvider::class,
        HashServiceProvider::class,
        BusServiceProvider::class,
        CacheServiceProvider::class,
        ConsoleSupportServiceProvider::class,
        DatabaseServiceProvider::class,
        EncryptionServiceProvider::class,
        FilesystemServiceProvider::class,
        FoundationServiceProvider::class,
        QueueServiceProvider::class,
        RedisServiceProvider::class,
        SessionServiceProvider::class,
        TranslationServiceProvider::class,
        ValidationServiceProvider::class,
        ViewServiceProvider::class,
        AppServiceProvider::class,
        YouTubeServiceProvider::class,
        SanctumServiceProvider::class,
        ReverbServiceProvider::class,
        HorizonServiceProvider::class,
    ],
    'aliases' => Facade::defaultAliases()->merge([])->toArray(),
];
