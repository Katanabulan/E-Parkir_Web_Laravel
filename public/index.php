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

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

if (isset($_SERVER['REQUEST_URI']) && isset($_SERVER['SCRIPT_NAME'])) {
    if (!str_contains($_SERVER['REQUEST_URI'], '/public/') && str_contains($_SERVER['SCRIPT_NAME'], '/public/')) {
        $_SERVER['SCRIPT_NAME'] = str_replace('/public', '', $_SERVER['SCRIPT_NAME']);
        if (isset($_SERVER['PHP_SELF'])) {
            $_SERVER['PHP_SELF'] = str_replace('/public', '', $_SERVER['PHP_SELF']);
        }
    }
}

// Global debug logger for mobile API calls
$reqUri = $_SERVER['REQUEST_URI'] ?? '';
if (!str_contains($reqUri, 'override') && !str_contains($reqUri, 'telemetry')) {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '-';
    $logLine = date('Y-m-d H:i:s') . " | {$remoteIp} | {$method} {$reqUri} | AUTH: {$authHeader}\n";
    @file_put_contents(__DIR__ . '/../storage/logs/mobile_debug.log', $logLine, FILE_APPEND);
}

$app->handleRequest(Request::capture());
