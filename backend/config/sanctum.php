<?php

$sanctumExpiration = env('SANCTUM_EXPIRATION');

return [
    'stateful' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,localhost:5173,127.0.0.1,127.0.0.1:5173'))
    ))),
    /**
     * Guard web para sessão HttpOnly da SPA; tokens Bearer (PAT) continuam a ser aceites pelo Sanctum.
     */
    'guard' => env('SANCTUM_GUARD')
        ? array_values(array_filter(explode(',', (string) env('SANCTUM_GUARD'))))
        : ['web'],
    /** Minutos até expirar tokens Sanctum (defina SANCTUM_EXPIRATION vazio para manter 1440). */
    'expiration' => $sanctumExpiration !== null && $sanctumExpiration !== '' ? (int) $sanctumExpiration : 1440,
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
