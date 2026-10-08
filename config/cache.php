<?php

return [
    'default' => env('CACHE_STORE', 'file'),
    'stores' => [
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data'), 'lock_path' => storage_path('framework/cache/data')],
        'array' => ['driver' => 'array', 'serialize' => false],
        'database' => ['driver' => 'database', 'table' => 'cache', 'connection' => null, 'lock_connection' => null],
    ],
    'prefix' => env('CACHE_PREFIX', str()->slug(env('APP_NAME', 'laravel'), '_').'_cache_'),
];
