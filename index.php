<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// maintenance mode
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// composer autoload (INI YANG BENAR)
require __DIR__.'/vendor/autoload.php';

// bootstrap laravel
$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
