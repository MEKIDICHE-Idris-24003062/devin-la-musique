<?php

declare(strict_types=1);

// Autoload (Composer si présent, sinon autoloader minimal PSR-4 App\\)
$composer = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composer)) {
    require $composer;
} else {
    spl_autoload_register(function (string $class): void {
        if (!str_starts_with($class, 'App\\')) return;
        $rel = substr($class, 4);
        $path = __DIR__ . '/' . str_replace('\\', '/', $rel) . '.php';
        if (file_exists($path)) require $path;
    });
}

require_once __DIR__ . '/helpers.php';

App\Env::load(__DIR__ . '/../.env');

// Sessions
session_name('dlm');
session_start();

// DB init
App\Db::init();
