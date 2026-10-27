<?php

declare(strict_types=1);

namespace Vsm\Modules\Auth;

use Vsm\Core\Access;
use Vsm\Core\Action;
use Vsm\Core\ApiException;
use Vsm\Core\Clock;
use Vsm\Core\Context;
use Vsm\Core\Env;
use Vsm\Core\Module;
use Vsm\Core\Request;
use Vsm\Core\Token;
use Vsm\Database\Connection;
use Vsm\Modules\Units\UnitRepository;

/** Login panel per unit (Unit 1 / Unit 2) + daftar unit untuk app Check-In / Check-Out. */
final class AuthModule implements Module
{
    public function __construct(
        private readonly Connection $db,
        private readonly UnitRepository $units,
    ) {}

    public function actions(): array
    {
        return [
            'PING'      => Action::get(Access::Public, fn () => ['message' => 'VSM Pasuruan API aktif.', 'driver' => $this->db->driver()]),
            'GET_UNITS' => Action::get(Access::Public, fn () => ['units' => $this->units->active()]),
            'LOGIN'     => Action::post(Access::Public, $this->login(...)),
            'ME'        => Action::get(Access::Auth, fn (Context $c) => ['user' => $this->present($c->user, $c->unit)]),
        ];
    }

    /** @param array<string,mixed> $d */
    private function login(Context $ctx, array $d, Request $req): array
    {
        $username = trim((string) ($d['username'] ?? ''));
        $password = (string) ($d['password'] ?? '');
        if ($username === '' || $password === '') {
            throw new ApiException('Username dan password wajib diisi.');
        }

        $rows = $this->db->select("SELECT * FROM users WHERE username = ? AND status = 'ACTIVE'", [$username]);
        $u = $rows[0] ?? null;
        // Verifikasi tetap dijalankan walau user tak ada, agar waktu respons seragam.
        $hash = $u['password_hash'] ?? password_hash('x', PASSWORD_DEFAULT);
        if (!password_verify($password, $hash) || !$u) {
            throw new ApiException('Username atau password salah.');
        }

        $requested = isset($d['unit']) ? $this->units->resolve($d['unit']) : null;
        if (!empty($d['unit']) && $requested === null) {
            throw new ApiException('Unit tidak dikenal.');
        }

        if ($u['role'] === 'admin') {
            $unit = $requested ?? $u['unit_id'] ?? 'U1';
        } else {
            $unit = (string) $u['unit_id'];
            if ($requested !== null && $requested !== $unit) {
                throw new ApiException('Akun ini terdaftar untuk ' . $this->units->name($unit) . ', bukan ' . $this->units->name($requested) . '.');
            }
        }

        $this->db->execute('UPDATE users SET last_login_at = ? WHERE id = ?', [Clock::now(), $u['id']]);

        $claims = ['uid' => (int) $u['id'], 'username' => $u['username'], 'name' => $u['name'], 'role' => $u['role'], 'unit' => $unit];
        $ttl = (int) Env::get('TOKEN_TTL_HOURS', '12') * 3600;

        return [
            'token' => Token::issue($claims, $ttl),
            'user'  => $this->present($claims, $unit),
        ];
    }

    /** @param array<string,mixed> $claims */
    private function present(array $claims, ?string $unit): array
    {
        return [
            'username' => $claims['username'],
            'name'     => $claims['name'],
            'role'     => $claims['role'],
            'unit'     => $unit,
            'unitName' => $unit ? $this->units->name($unit) : null,
            'units'    => $claims['role'] === 'admin' ? $this->units->active() : [],
        ];
    }
}
