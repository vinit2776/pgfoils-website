<?php

// Vercel entry point. The function filesystem is read-only except /tmp, so Laravel's
// writable paths are moved there before the app boots.
$tmp = '/tmp/laravel';
foreach (['storage/framework/views', 'storage/framework/cache', 'storage/framework/sessions', 'storage/logs', 'bootstrap/cache'] as $dir) {
    if (! is_dir("$tmp/$dir")) {
        mkdir("$tmp/$dir", 0775, true);
    }
}
foreach (['PACKAGES' => 'packages', 'SERVICES' => 'services', 'CONFIG' => 'config', 'ROUTES' => 'routes-v7', 'EVENTS' => 'events'] as $key => $file) {
    putenv("APP_{$key}_CACHE=$tmp/bootstrap/cache/$file.php");
    $_ENV["APP_{$key}_CACHE"] = "$tmp/bootstrap/cache/$file.php";
}
putenv("VIEW_COMPILED_PATH=$tmp/storage/framework/views");
$_ENV['VIEW_COMPILED_PATH'] = "$tmp/storage/framework/views";

// Until a real database is configured (DB_HOST), fall back to the read-only snapshot built by scripts/vercel-prepare.sh.
if (! getenv('DB_HOST')) {
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE='.realpath(__DIR__.'/../database/deploy.sqlite'));
    putenv('SESSION_DRIVER=cookie');
    putenv('CACHE_STORE=array');
    $_ENV['SESSION_DRIVER'] = 'cookie';
    $_ENV['CACHE_STORE'] = 'array';
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_ENV['DB_DATABASE'] = realpath(__DIR__.'/../database/deploy.sqlite');
}

// Static files that sit in public/ are served by Vercel; PHP only sees the rest.
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
