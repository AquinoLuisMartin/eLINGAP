<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

$storage = sys_get_temp_dir().'/elingap';

foreach (['framework/cache/data', 'framework/sessions', 'framework/views', 'logs', 'app'] as $directory) {
    $path = $storage.'/'.$directory;

    if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
        throw new RuntimeException('Unable to initialize temporary application storage.');
    }
}

/** @var Application $app */
$app = require __DIR__.'/../bootstrap/app.php';

$app->useStoragePath($storage);
$app->booting(function () use ($app): void {
    // Keep permanent uploads off temporary storage until a durable disk is configured.
    $app['config']->set('filesystems.disks.local.root', $app->basePath('storage/app/private'));
    $app['config']->set('filesystems.disks.public.root', $app->basePath('storage/app/public'));
});

$app->handleRequest(Request::capture());
