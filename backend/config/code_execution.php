<?php

return [
    'timeout' => env('CODE_EXECUTION_TIMEOUT', 10),
    'memory_limit' => env('CODE_EXECUTION_MEMORY_LIMIT', '128m'),
    'max_code_length' => env('CODE_EXECUTION_MAX_CODE_LENGTH', 10000),
    'supported_languages' => ['javascript', 'php', 'lua', 'html', 'css', 'sql', 'laravel'],
    'client_side_languages' => ['javascript', 'lua', 'html', 'css'],
];
