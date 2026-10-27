<?php

declare(strict_types=1);

namespace Vsm\Core;

/**
 * Token bertanda tangan HMAC-SHA256 (stateless) — cocok untuk Vercel
 * serverless yang tidak punya session PHP persisten.
 * Format: base64url(payload).base64url(signature)
 */
final class Token
{
    public static function issue(array $claims, int $ttl): string
    {
        $claims['exp'] = time() + $ttl;
        $body = self::b64(json_encode($claims, JSON_UNESCAPED_UNICODE));
        return $body . '.' . self::b64(hash_hmac('sha256', $body, self::key(), true));
    }

    /** @return array<string,mixed>|null */
    public static function verify(?string $token): ?array
    {
        if (!$token || substr_count($token, '.') !== 1) {
            return null;
        }
        [$body, $sig] = explode('.', $token);
        if (!hash_equals(self::b64(hash_hmac('sha256', $body, self::key(), true)), $sig)) {
            return null;
        }
        $claims = json_decode((string) base64_decode(strtr($body, '-_', '+/')), true);
        if (!is_array($claims) || ($claims['exp'] ?? 0) < time()) {
            return null;
        }
        return $claims;
    }

    private static function key(): string
    {
        $key = Env::get('APP_KEY');
        if (!$key || strlen($key) < 16) {
            throw new \RuntimeException('APP_KEY belum diset (minimal 16 karakter) di .env / environment.');
        }
        return $key;
    }

    private static function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }
}
