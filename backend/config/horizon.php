<?php

use Illuminate\Support\Str;

return [
    'use' => env('HORIZON_REDIS_CONNECTION', 'horizon'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'),
    'middleware' => ['web'],
    'waits' => [
        'redis:default' => 60,
        'redis:metrics' => 60,
        'redis:scheduler' => 120,
    ],
    'trim' => [
        'recent' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
    ],
    'defaults' => [
        'supervisor-default' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'maxProcesses' => 1,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
        ],
        'supervisor-metrics' => [
            'connection' => 'redis',
            'queue' => ['metrics'],
            'balance' => 'simple',
            'maxProcesses' => 1,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 90,
        ],
        'supervisor-scheduler' => [
            'connection' => 'redis',
            'queue' => ['scheduler'],
            'balance' => 'simple',
            'maxProcesses' => 1,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 120,
        ],
    ],
    'environments' => [
        'production' => [
            'supervisor-default' => [
                'maxProcesses' => 5,
            ],
            'supervisor-metrics' => [
                'maxProcesses' => 3,
            ],
            'supervisor-scheduler' => [
                'maxProcesses' => 1,
            ],
        ],
        'local' => [
            'supervisor-default' => [
                'maxProcesses' => 3,
            ],
            'supervisor-metrics' => [
                'maxProcesses' => 2,
            ],
            'supervisor-scheduler' => [
                'maxProcesses' => 1,
            ],
        ],
    ],
];
