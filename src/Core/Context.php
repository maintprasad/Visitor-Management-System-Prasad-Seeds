<?php

declare(strict_types=1);

namespace Vsm\Core;

/** Siapa yang memanggil + unit mana yang sedang dipakai. Seluruh query data WAJIB memakai $unit. */
final class Context
{
    public function __construct(
        public readonly ?array $user,
        public readonly ?string $unit,
    ) {}

    public function isAuthenticated(): bool
    {
        return $this->user !== null;
    }

    public function isAdmin(): bool
    {
        return ($this->user['role'] ?? '') === 'admin';
    }

    public function userName(string $fallback = 'System'): string
    {
        return (string) ($this->user['name'] ?? $fallback);
    }

    /** Unit aktif; error bila belum ditentukan. */
    public function requireUnit(): string
    {
        if ($this->unit === null) {
            throw new ApiException('Unit belum ditentukan. Sertakan parameter unit (U1 / U2).');
        }
        return $this->unit;
    }
}
