<?php

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),
    /**
     * Guards consultados antes do Bearer. Por defeito vazio (API só com token).
     * Para SPA (sessão + Sanctum), defina no .env: SANCTUM_GUARD=web
     */
    'guard' => env('SANCTUM_GUARD')
        ? array_values(array_filter(explode(',', (string) env('SANCTUM_GUARD'))))
        : [],
    'expiration' => 1440,
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
