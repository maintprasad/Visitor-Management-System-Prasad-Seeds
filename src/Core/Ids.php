<?php

declare(strict_types=1);

namespace Vsm\Core;

final class Ids
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public static function make(string $prefix): string
    {
        $s = '';
        for ($i = 0; $i < 8; $i++) {
            $s .= self::ALPHABET[random_int(0, 35)];
        }
        return $prefix . '-' . $s;
    }

    /** Terima ID buatan klien hanya bila bentuknya wajar (PREFIX-XXXXXXXX). */
    public static function valid(mixed $id, string $prefix): bool
    {
        return is_string($id) && (bool) preg_match('/^' . preg_quote($prefix, '/') . '-[A-Z0-9]{6,12}$/', $id);
    }
}
