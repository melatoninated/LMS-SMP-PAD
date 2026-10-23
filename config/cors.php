<?php

$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://127.0.0.1:3000,http://localhost:5173,http://127.0.0.1:5173'))
)));

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // API ini memakai Bearer token, bukan cookie sesi lintas-domain.
    // Tambahkan URL frontend/tunnel ke CORS_ALLOWED_ORIGINS tanpa mengubah kode.
    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
