<?php

// Usar 'log' quando Reverb está selecionado mas credenciais não estão configuradas (evita crash).
$requested = env('BROADCAST_CONNECTION', 'reverb');
$reverbReady = $requested === 'reverb'
    && env('REVERB_APP_ID')
    && env('REVERB_APP_KEY')
    && env('REVERB_APP_SECRET');

return [
    'default' => $reverbReady ? 'reverb' : 'log',
    'connections' => [
        'log' => [
            'driver' => 'log',
        ],
        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST', 'localhost'),
                'port' => env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
            ],
        ],
    ],
];
