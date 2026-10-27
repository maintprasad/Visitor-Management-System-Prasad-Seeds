<?php

declare(strict_types=1);

namespace Vsm\Core;

/** Semua waktu disimpan sebagai ISO-8601 UTC (sama dengan format di spreadsheet & frontend). */
final class Clock
{
    public static function now(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');
    }

    public static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }
        try {
            $d = new \DateTimeImmutable((string) $value);
        } catch (\Throwable) {
            return null;
        }
        return $d->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.v\Z');
    }

    /** Tanggal lokal (zona APP_TIMEZONE) untuk penomoran, mis. 20260624. */
    public static function localDate(): string
    {
        return (new \DateTimeImmutable('now'))->format('Ymd');
    }

    /** Selisih hari (pecahan) — satuan yang sama dengan kolom Duration di Excel. */
    public static function daysBetween(?string $from, ?string $to): ?float
    {
        $a = self::normalize($from);
        $b = self::normalize($to);
        if (!$a || !$b) {
            return null;
        }
        return (strtotime($b) - strtotime($a)) / 86400;
    }
}
