<?php

return [
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

    'rate_limit' => [
        // Fail-open: se o Redis cair, requisições passam em vez de 503 (evita DDoS autoinfligido).
        'fail_open' => env('RATE_LIMIT_FAIL_OPEN', true),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'discord' => [
        'client_id' => env('DISCORD_CLIENT_ID'),
        'client_secret' => env('DISCORD_CLIENT_SECRET'),
        'redirect' => env('DISCORD_REDIRECT_URI'),
        'scopes' => ['identify', 'email'],
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI'),
    ],

    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY'),
    ],

    'math' => [
        'url' => env('MATH_SERVICE_URL', 'http://math-service:8000'),
        'token' => env('MATH_SERVICE_TOKEN'),
    ],
];
