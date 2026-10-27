<?php

declare(strict_types=1);

/*
 * Bootstrap bersama: dipakai oleh public/index.php (lokal/Apache),
 * api/index.php (Vercel) dan skrip CLI di bin/.
 * Tanpa Composer — autoloader PSR-4 sederhana untuk namespace Vsm\.
 */

define('BASE_PATH', __DIR__);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Vsm\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = BASE_PATH . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

Vsm\Core\Env::load(BASE_PATH . '/.env');

date_default_timezone_set(Vsm\Core\Env::get('APP_TIMEZONE', 'Asia/Jakarta'));
mb_internal_encoding('UTF-8');
