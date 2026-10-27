<?php

declare(strict_types=1);

/*
 * Titik masuk Vercel (runtime PHP komunitas, lihat vercel.json).
 * Semua request non-statis dirutekan ke sini.
 */

require dirname(__DIR__) . '/bootstrap.php';

(new Vsm\App())->handle(Vsm\Core\Request::capture());
