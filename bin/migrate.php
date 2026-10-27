<?php

declare(strict_types=1);

/*
 * Membuat / memperbarui struktur database sesuai DB_DRIVER di .env
 *   php bin/migrate.php
 * Aman dijalankan berulang. Jalankan sekali dengan DB_DRIVER=mysql (lokal)
 * dan sekali dengan DB_DRIVER=turso (sebelum deploy Vercel).
 */

require dirname(__DIR__) . '/bootstrap.php';

use Vsm\Database\Database;
use Vsm\Database\Migrator;

$db = Database::connection();
$n  = (new Migrator($db))->migrate();
echo "OK  driver={$db->driver()}  statement dijalankan: {$n}  (unit U1 & U2 siap)\n";
