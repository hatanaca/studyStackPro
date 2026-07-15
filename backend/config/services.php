<?php

return [
    'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),

    'rate_limit' => [
        'fail_open' => env('RATE_LIMIT_FAIL_OPEN', false),
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
        'scopes' => ['identify', 'email', 'guilds', 'messages.read'],
    ],

    'linkedin' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI'),
    ],

    'youtube' => [
        'api_key' => env('YOUTUBE_API_KEY'),
    ],
];
