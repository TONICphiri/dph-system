<?php

/*
|--------------------------------------------------------------------------
| Vercel serverless entrypoint
|--------------------------------------------------------------------------
|
| vercel.json routes requests here when no static file matches. Static files
| in public/ are served as a fallback with cache headers; application routes
| are handed to Laravel's normal public/index.php entrypoint.
|
*/

// The filesystem is read-only on Vercel except /tmp, so make sure the
// compiled Blade views folder exists before Laravel needs it.
$viewsPath = getenv('VIEW_COMPILED_PATH') ?: '/tmp/views';

if (! is_dir($viewsPath)) {
    @mkdir($viewsPath, 0755, true);
}

$requestPath = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

if ($requestPath === '') {
    $requestPath = '/';
}

$publicRoot = realpath(__DIR__.'/../public');

if ($publicRoot !== false && $requestPath !== '/' && ! str_contains($requestPath, "\0")) {
    $file = realpath($publicRoot.$requestPath);

    if ($file !== false
        && str_starts_with($file, $publicRoot.DIRECTORY_SEPARATOR)
        && is_file($file)) {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        $mimeTypes = [
            'avif' => 'image/avif',
            'css' => 'text/css; charset=UTF-8',
            'eot' => 'application/vnd.ms-fontobject',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'map' => 'application/json; charset=UTF-8',
            'mp4' => 'video/mp4',
            'otf' => 'font/otf',
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'ttf' => 'font/ttf',
            'txt' => 'text/plain; charset=UTF-8',
            'wasm' => 'application/wasm',
            'webmanifest' => 'application/manifest+json; charset=UTF-8',
            'webm' => 'video/webm',
            'webp' => 'image/webp',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'xml' => 'application/xml; charset=UTF-8',
        ];

        if (isset($mimeTypes[$extension])) {
            $isImmutable = str_starts_with($requestPath, '/build/assets/');
            $isRevalidated = in_array($extension, ['js', 'webmanifest'], true) && ! $isImmutable;

            $cacheControl = $isImmutable
                ? 'public, max-age=31536000, s-maxage=31536000, immutable'
                : ($isRevalidated
                    ? 'public, max-age=0, s-maxage=0, must-revalidate'
                    : 'public, max-age=86400, s-maxage=86400');

            header('Content-Type: '.$mimeTypes[$extension]);
            header('Content-Length: '.(string) filesize($file));
            header('Cache-Control: '.$cacheControl);
            header('X-Content-Type-Options: nosniff');

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
                readfile($file);
            }

            return;
        }
    }
}

require __DIR__.'/../public/index.php';