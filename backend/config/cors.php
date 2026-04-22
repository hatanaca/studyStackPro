<?php

$rawCorsOrigins = env('CORS_ALLOWED_ORIGINS');
$allowedOrigins = is_string($rawCorsOrigins) && trim($rawCorsOrigins) !== ''
    ? array_values(array_filter(array_map('trim', explode(',', $rawCorsOrigins))))
    : [];

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    // Cookies de sessão Sanctum (SPA): só com pelo menos uma origem válida (ignora entradas vazias).
    'supports_credentials' => $allowedOrigins !== [],
];
