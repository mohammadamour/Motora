<?php

// 0. Suppress PHP deprecation warnings and hide errors from HTTP output in production
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// 1. Prepare writable storage directories in /tmp for serverless execution
$dirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 2. Fix SCRIPT_NAME and SCRIPT_FILENAME so Laravel knows base URL is root /
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';

// 3. Ensure HTTPS is detected behind Vercel reverse proxy
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

// 4. Copy pre-seeded SQLite database to writable /tmp on cold start
$sqliteTarget = '/tmp/database.sqlite';
$sqliteSource = __DIR__ . '/../database/database.sqlite';
if (!file_exists($sqliteTarget) && file_exists($sqliteSource)) {
    @copy($sqliteSource, $sqliteTarget);
}

// 5. Set environment variable paths for serverless
putenv('APP_STORAGE=/tmp/storage');
putenv('VIEW_COMPILED_PATH=/tmp/storage/framework/views');
putenv('APP_CONFIG_CACHE=/tmp/bootstrap/cache/config.php');
putenv('APP_EVENTS_CACHE=/tmp/bootstrap/cache/events.php');
putenv('APP_PACKAGES_CACHE=/tmp/bootstrap/cache/packages.php');
putenv('APP_ROUTES_CACHE=/tmp/bootstrap/cache/routes.php');
putenv('APP_SERVICES_CACHE=/tmp/bootstrap/cache/services.php');

// 6. Database — SQLite in /tmp
if (!getenv('DB_CONNECTION') || getenv('DB_CONNECTION') === 'sqlite') {
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE=' . $sqliteTarget);
}

// 7. Serverless-compatible drivers
if (!getenv('SESSION_DRIVER')) {
    putenv('SESSION_DRIVER=cookie');
}

if (!getenv('CACHE_STORE')) {
    putenv('CACHE_STORE=array');
}

if (!getenv('LOG_CHANNEL')) {
    putenv('LOG_CHANNEL=stderr');
}

if (!getenv('QUEUE_CONNECTION')) {
    putenv('QUEUE_CONNECTION=sync');
}

// 8. Forward execution to Laravel's public entrypoint
require __DIR__ . '/../public/index.php';

