<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Friendly setup error handler for cPanel shared hosting environment
if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><title>Setup Required - Taskwala</title><style>body{font-family:sans-serif;padding:40px;background:#f8fafc;color:#1e293b;} .card{background:#fff;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);max-width:600px;margin:auto;} code{background:#e2e8f0;padding:2px 6px;border-radius:4px;}</style></head><body>';
    echo '<div class="card"><h2>Taskwala Deployment Setup Required</h2>';
    echo '<p>The Composer dependencies (<code>vendor/</code> directory) have not been installed yet on your cPanel server.</p>';
    echo '<p><strong>To fix this error:</strong> Log into cPanel > Terminal (or SSH) and run:</p>';
    echo '<pre style="background:#0f172a;color:#38bdf8;padding:15px;border-radius:8px;">composer install --no-dev --optimize-autoloader</pre>';
    echo '</div></body></html>';
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
