<?php

declare(strict_types=1);

namespace Vsm\Core;

/** Membaca .env (lokal) dengan fallback ke environment proses (Vercel). */
final class Env
{
    /** @var array<string,string> */
    private static array $vars = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $v = trim($v);
            if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && $v[-1] === $v[0]) {
                $v = substr($v, 1, -1);
            }
            self::$vars[trim($k)] = $v;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        // Environment proses (Vercel, atau "DB_DRIVER=turso php bin/..." di terminal) menang atas file .env.
        $v = getenv($key);
        if ($v !== false) {
            return $v;
        }
        return self::$vars[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        return $v === null ? $default : in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
    }
}
