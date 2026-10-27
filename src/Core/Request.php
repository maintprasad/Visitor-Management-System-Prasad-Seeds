<?php

declare(strict_types=1);

namespace Vsm\Core;

final class Request
{
    /** @param array<string,mixed> $query @param array<string,mixed> $body @param array<string,string> $headers */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        private readonly array $headers,
    ) {}

    public static function capture(): self
    {
        $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $base = self::basePath();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $raw  = (string) file_get_contents('php://input');
        $body = $raw !== '' ? json_decode($raw, true) : [];

        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($k, 5)))] = (string) $v;
            }
        }
        // Apache sering membuang Authorization; ambil dari REDIRECT_ bila perlu.
        if (!isset($headers['authorization']) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            rtrim($path, '/') ?: '/',
            $_GET,
            is_array($body) ? $body : [],
            $headers,
        );
    }

    /** Prefix URL bila aplikasi dipasang di subfolder (mis. /vsm_pasuruan/4.../public di XAMPP). */
    public static function basePath(): string
    {
        $env = Env::get('APP_BASE_PATH');
        if ($env !== null) {
            return rtrim($env, '/');
        }
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        return str_ends_with($dir, '/api') ? '' : $dir;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function bearer(): ?string
    {
        $h = $this->header('authorization');
        return ($h && preg_match('/^Bearer\s+(.+)$/i', $h, $m)) ? trim($m[1]) : null;
    }
}
