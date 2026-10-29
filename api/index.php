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

// Read-only copy of the public content (pages and the Investors Corner list), built by scripts/vercel-prepare.sh.
putenv('DB_DATABASE='.realpath(__DIR__.'/../database/deploy.sqlite'));
$_ENV['DB_DATABASE'] = realpath(__DIR__.'/../database/deploy.sqlite');

// Static files that sit in public/ are served by Vercel; PHP only sees the rest.
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

require __DIR__.'/../public/index.php';
