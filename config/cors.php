<?php

return [

    'enabled' => env('CORS_ENABLED', true),

    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', 'https://your-domain.mw'),

    'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')),

    'allowed_headers' => explode(',', env('CORS_ALLOWED_HEADERS', 'Origin,Content-Type,Accept,Authorization,X-Requested-With,X-CSRF-TOKEN')),

    'exposed_headers' => explode(',', env('CORS_EXPOSED_HEADERS', '')),

    'allow_credentials' => (bool) env('CORS_ALLOW_CREDENTIALS', true),

    'max_age' => env('CORS_MAX_AGE', 3600),

];
