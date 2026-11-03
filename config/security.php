<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Controls whether security headers are added to all responses.
    | These protect against clickjacking, MIME sniffing, XSS, and more.
    |
    */

    'headers' => [
        'enabled' => env('SECURE_HEADERS', true),
        'x_frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_xss_protection' => env('X_XSS_PROTECTION', '1; mode=block'),
        'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'content_security_policy' => env('CONTENT_SECURITY_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self';"),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Controls request rate limiting to protect against brute-force attacks
    | and API abuse.
    |
    */

    'rate_limit' => [
        'enabled' => env('RATE_LIMIT_ENABLED', true),
        'limits' => [
            'login' => env('RATE_LIMIT_LOGIN', 5),
            'api' => env('RATE_LIMIT_API', 60),
            'register' => env('RATE_LIMIT_REGISTER', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTPS Enforcement
    |--------------------------------------------------------------------------
    |
    | When true, the application will force HTTPS connections.
    |
    */

    'force_https' => env('FORCE_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | Limits for file uploads to prevent abuse.
    |
    */

    'uploads' => [
        'max_size' => env('MAX_UPLOAD_SIZE', 10485760), // 10MB
        'allowed_mime_types' => explode(',', env('ALLOWED_MIME_TYPES', 'image/jpeg,image/png,image/webp,application/pdf')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring
    |--------------------------------------------------------------------------
    |
    | Settings for monitoring and responding to security events.
    |
    */

    'monitoring' => [
        'log_channel' => env('SECURITY_LOG_CHANNEL', 'security'),
        'failed_login_lockout_minutes' => env('FAILED_LOGIN_LOCKOUT_MINUTES', 15),
        'max_failed_login_attempts' => env('MAX_FAILED_LOGIN_ATTEMPTS', 5),
    ],

];
