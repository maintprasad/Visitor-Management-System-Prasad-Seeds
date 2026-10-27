<?php

declare(strict_types=1);

namespace Vsm\Core;

final class Response
{
    /** @param array<string,mixed> $payload */
    public static function json(array $payload, int $status = 200): never
    {
        self::cors();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /** CORS untuk aplikasi Check-In / Check-Out yang di-host terpisah. */
    public static function cors(): void
    {
        $allowed = array_filter(array_map('trim', explode(',', Env::get('CORS_ORIGINS', '*') ?? '*')));
        $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (in_array('*', $allowed, true)) {
            header('Access-Control-Allow-Origin: *');
        } elseif ($origin !== '' && in_array($origin, $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Unit');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Max-Age: 86400');
    }

    public static function html(string $html, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}
