<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Ensure cURL/OpenSSL uses a valid CA bundle if provided.
$caBundle = getenv('CURL_CA_BUNDLE') ?: getenv('SSL_CERT_FILE');
if ($caBundle && file_exists($caBundle)) {
    ini_set('curl.cainfo', $caBundle);
    ini_set('openssl.cafile', $caBundle);
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
