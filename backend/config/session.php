<?php

return [
    'driver' => env('SESSION_DRIVER', 'redis'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION', env('SESSION_DRIVER') === 'redis' ? 'session' : null),
    'table' => 'sessions',
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', 'studytrack_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN'),
    'secure' => filter_var(
        env('SESSION_SECURE_COOKIE', env('APP_ENV', 'local') === 'production'),
        FILTER_VALIDATE_BOOL
    ),
    'http_only' => true,
    'same_site' => 'lax',
];
