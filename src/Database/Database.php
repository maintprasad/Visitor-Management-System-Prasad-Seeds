<?php

declare(strict_types=1);

namespace Vsm\Database;

use Vsm\Core\Env;

/** Pilih driver lewat DB_DRIVER=mysql (localhost/XAMPP) atau DB_DRIVER=turso (Vercel). */
final class Database
{
    private static ?Connection $conn = null;

    public static function connection(): Connection
    {
        return self::$conn ??= self::make(self::resolveDriver());
    }

    /**
     * DB_DRIVER bila diisi. Bila kosong: otomatis Turso kalau kredensial Turso ada
     * (kasus Vercel / integrasi Turso yang hanya menyuntik URL + token), selain itu MySQL.
     */
    public static function resolveDriver(): string
    {
        $driver = strtolower(trim((string) Env::get('DB_DRIVER', '')));
        if ($driver !== '') {
            return $driver;
        }
        $url = (string) Env::get('TURSO_DATABASE_URL', '');
        $token = (string) Env::get('TURSO_AUTH_TOKEN', '');
        return ($url !== '' && $token !== '' && !str_contains($url, 'nama-db-anda')) ? 'turso' : 'mysql';
    }

    public static function make(string $driver): Connection
    {
        return match ($driver) {
            'mysql' => new MysqlConnection(
                Env::get('DB_HOST', '127.0.0.1'),
                (int) Env::get('DB_PORT', '3306'),
                Env::get('DB_DATABASE', 'vsm_pasuruan'),
                Env::get('DB_USERNAME', 'root'),
                Env::get('DB_PASSWORD', ''),
            ),
            'turso' => new TursoConnection(
                Env::get('TURSO_DATABASE_URL') ?? throw new \RuntimeException('TURSO_DATABASE_URL belum diset.'),
                Env::get('TURSO_AUTH_TOKEN') ?? throw new \RuntimeException('TURSO_AUTH_TOKEN belum diset.'),
            ),
            default => throw new \RuntimeException("DB_DRIVER tidak dikenal: {$driver} (pakai mysql atau turso)"),
        };
    }
}
