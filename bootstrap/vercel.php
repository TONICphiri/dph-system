<?php

/*
|--------------------------------------------------------------------------
| Vercel / serverless bootstrap
|--------------------------------------------------------------------------
|
| Vercel's function filesystem is read-only apart from /tmp. Laravel writes
| compiled views, logs and local files under storage/, so point that at /tmp
| before the application boots. This file is a no-op outside Vercel.
|
*/

if (! getenv('VERCEL') && ! getenv('VERCEL_ENV')) {
    return null;
}

$setEnv = static function (string $key, string $value, bool $force = false): void {
    if (! $force) {
        if (getenv($key) !== false && getenv($key) !== '') {
            return;
        }

        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return;
        }

        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return;
        }
    }

    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
};

$storagePath = rtrim((string) (getenv('APP_STORAGE_PATH') ?: '/tmp/storage'), '/');

foreach ([
    '',
    '/app/private',
    '/app/public',
    '/bootstrap',
    '/framework/cache/data',
    '/framework/sessions',
    '/framework/views',
    '/framework/testing',
    '/logs',
] as $directory) {
    $path = $storagePath.$directory;

    if (! is_dir($path) && ! @mkdir($path, 0777, true) && ! is_dir($path)) {
        throw new RuntimeException('Unable to create writable storage directory: '.$path);
    }
}

$setEnv('APP_STORAGE_PATH', $storagePath);
$setEnv('LOG_CHANNEL', 'stderr');

// The runtime runs `composer run vercel` (package discovery) at build time.
// If that step was skipped, let Laravel write its caches to /tmp instead.
foreach (['packages', 'services'] as $cache) {
    if (! is_file(__DIR__.'/cache/'.$cache.'.php')) {
        $setEnv('APP_'.strtoupper($cache).'_CACHE', $storagePath.'/bootstrap/'.$cache.'.php');
    }
}

// Vercel terminates TLS at its edge. Use the direct caller as the trusted
// proxy so X-Forwarded-For is honoured without trusting arbitrary addresses.
$trustedProxies = trim((string) (getenv('TRUSTED_PROXIES') ?: ''));

if ($trustedProxies === '' || $trustedProxies === '127.0.0.1' || $trustedProxies === 'localhost') {
    $setEnv('TRUSTED_PROXIES', 'REMOTE_ADDR', true);
}

$setEnv('SESSION_SECURE_COOKIE', 'true', true);
$setEnv('FORCE_HTTPS', 'true', true);

$appUrl = (string) (getenv('APP_URL') ?: '');

if ($appUrl === '' || str_contains($appUrl, 'localhost') || str_contains($appUrl, 'your-domain')) {
    $host = getenv('VERCEL_ENV') === 'production'
        ? (getenv('VERCEL_PROJECT_PRODUCTION_URL') ?: getenv('VERCEL_URL'))
        : getenv('VERCEL_URL');

    $host = preg_replace('#^https?://#', '', (string) $host);

    if ($host) {
        $setEnv('APP_URL', 'https://'.$host, true);
    }
}

if (! getenv('BACKUP_DISK') && getenv('AWS_BUCKET')) {
    $setEnv('BACKUP_DISK', 's3');
}

return $storagePath;
