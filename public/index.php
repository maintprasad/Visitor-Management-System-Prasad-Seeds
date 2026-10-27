<?php

declare(strict_types=1);

/*
 * Titik masuk lokal.
 *  - Apache/XAMPP : .htaccess mengarahkan semua yang bukan file ke sini.
 *  - php -S       : php -S localhost:8000 -t public public/index.php
 */

// php -S: biarkan file statis (css/js/img) dilayani langsung.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if ($path !== '/' && is_file(__DIR__ . $path)) {
        return false;
    }
}

require dirname(__DIR__) . '/bootstrap.php';

(new Vsm\App())->handle(Vsm\Core\Request::capture());
