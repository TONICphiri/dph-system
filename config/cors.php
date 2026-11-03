<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing
|--------------------------------------------------------------------------
|
| This application is server-rendered and same-origin, so CORS is off by
| default. Enable it only when another origin must call the application.
|
*/

$list = static function (string $key, string $default = ''): array {
    return array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env($key, $default))
    )));
};

return [

    'enabled' => (bool) env('CORS_ENABLED', false),

    'allowed_origins' => $list('CORS_ALLOWED_ORIGINS'),

    'allowed_methods' => $list('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS'),

    'allowed_headers' => $list('CORS_ALLOWED_HEADERS', 'Origin,Content-Type,Accept,Authorization,X-Requested-With,X-CSRF-TOKEN'),

    'exposed_headers' => $list('CORS_EXPOSED_HEADERS'),

    'allow_credentials' => (bool) env('CORS_ALLOW_CREDENTIALS', false),

    'max_age' => (int) env('CORS_MAX_AGE', 3600),

];
