<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Vercel's filesystem is read-only; this points storage at /tmp when needed.
$vercelStoragePath = require __DIR__.'/../bootstrap/vercel.php';
$storagePath = $vercelStoragePath ?: __DIR__.'/../storage';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $storagePath.'/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

if ($vercelStoragePath) {
    $app->useStoragePath($vercelStoragePath);
}

$app->handleRequest(Request::capture());
