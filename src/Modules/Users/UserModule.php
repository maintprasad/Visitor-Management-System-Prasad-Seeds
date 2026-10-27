<?php

declare(strict_types=1);

namespace Vsm\Modules\Users;

use Vsm\Core\Access;
use Vsm\Core\Action;
use Vsm\Core\ApiException;
use Vsm\Core\Clock;
use Vsm\Core\Context;
use Vsm\Core\Module;
use Vsm\Core\Request;
use Vsm\Database\Connection;
use Vsm\Modules\Logs\LogRepository;
use Vsm\Modules\Units\UnitRepository;

/**
 * Manajemen akun (khusus admin): tambah, ubah, reset password, nonaktifkan, hapus.
 * Akun yang sama dipakai login Main Panel dan app Visitor Check-Out.
 */
final class UserModule implements Module
{
    public const ROLES = ['security', 'admin'];
    public const STATUSES = ['ACTIVE', 'DISABLED'];
    public const MIN_PASSWORD = 8;

    public function __construct(
        private readonly Connection $db,
        private readonly UnitRepository $units,
        private readonly LogRepository $logs,
    ) {}

    public function actions(): array
    {
        return [
            'LIST_USERS'  => Action::get(Access::Admin, $this->list(...)),
            'CREATE_USER' => Action::post(Access::Admin, $this->create(...)),
            'UPDATE_USER' => Action::post(Access::Admin, $this->update(...)),
            'DELETE_USER' => Action::post(Access::Admin, $this->delete(...)),
        ];
    }

    /** @param array<string,mixed> $q */
    private function list(Context $ctx, array $q, Request $req): array
    {
        $rows = $this->db->select('SELECT * FROM users ORDER BY role, unit_id, username');
        return ['users' => array_map($this->present(...), $rows), 'units' => $this->units->active()];
    }

    /** @param array<string,mixed> $d */
    private function create(Context $ctx, array $d, Request $req): array
    {
        $username = strtolower(trim((string) ($d['username'] ?? '')));
        if (!preg_match('/^[a-z0-9._-]{3,40}$/', $username)) {
            throw new ApiException('Username 3-40 karakter: huruf kecil, angka, titik, strip, atau underscore.');
        }
        if ($this->db->select('SELECT 1 AS x FROM users WHERE username = ?', [$username])) {
            throw new ApiException('Username "' . $username . '" sudah dipakai.');
        }
        $name = $this->cleanName($d['name'] ?? '');
        $role = $this->cleanRole($d['role'] ?? 'security');
        $unit = $this->cleanUnit($role, $d['unit'] ?? null);
        $password = $this->cleanPassword($d['password'] ?? '');

        $now = Clock::now();
        $this->db->execute(
            'INSERT INTO users (username, password_hash, name, role, unit_id, status, created_at, created_by, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$username, password_hash($password, PASSWORD_DEFAULT), $name, $role, $unit, 'ACTIVE', $now, $ctx->user['username'] ?? null, $now]
        );
        $this->audit($ctx, 'USER_CREATE', $username, "Akun dibuat: {$username} ({$role}" . ($unit ? ", {$unit}" : '') . ')');
        return ['id' => (int) $this->db->select('SELECT id FROM users WHERE username = ?', [$username])[0]['id']];
    }

    /** @param array<string,mixed> $d */
    private function update(Context $ctx, array $d, Request $req): array
    {
        $u = $this->mustFind($d['id'] ?? null);
        $self = (int) $u['id'] === (int) ($ctx->user['uid'] ?? 0);

        $name   = array_key_exists('name', $d) ? $this->cleanName($d['name']) : $u['name'];
        $role   = array_key_exists('role', $d) ? $this->cleanRole($d['role']) : $u['role'];
        $status = array_key_exists('status', $d) ? (string) $d['status'] : $u['status'];
        $unit   = array_key_exists('unit', $d) || array_key_exists('role', $d)
            ? $this->cleanUnit($role, $d['unit'] ?? $u['unit_id'])
            : $u['unit_id'];
        if (!in_array($status, self::STATUSES, true)) {
            throw new ApiException('Status harus ACTIVE atau DISABLED.');
        }
        if ($self && ($role !== 'admin' || $status !== 'ACTIVE')) {
            throw new ApiException('Anda tidak bisa menurunkan atau menonaktifkan akun Anda sendiri.');
        }
        if ($u['role'] === 'admin' && $u['status'] === 'ACTIVE' && ($role !== 'admin' || $status !== 'ACTIVE')) {
            $this->assertOtherActiveAdmin((int) $u['id']);
        }

        $set = ['name = ?', 'role = ?', 'unit_id = ?', 'status = ?', 'updated_at = ?'];
        $params = [$name, $role, $unit, $status, Clock::now()];
        if (isset($d['password']) && $d['password'] !== '') {
            $set[] = 'password_hash = ?';
            $params[] = password_hash($this->cleanPassword($d['password']), PASSWORD_DEFAULT);
        }
        $params[] = (int) $u['id'];
        $this->db->execute('UPDATE users SET ' . implode(', ', $set) . ' WHERE id = ?', $params);

        $this->audit($ctx, 'USER_UPDATE', $u['username'], "Akun diubah: {$u['username']} ({$role}" . ($unit ? ", {$unit}" : '') . ", {$status})"
            . (isset($d['password']) && $d['password'] !== '' ? ' + reset password' : ''));
        return ['id' => (int) $u['id']];
    }

    /** @param array<string,mixed> $d */
    private function delete(Context $ctx, array $d, Request $req): array
    {
        $u = $this->mustFind($d['id'] ?? null);
        if ((int) $u['id'] === (int) ($ctx->user['uid'] ?? 0)) {
            throw new ApiException('Anda tidak bisa menghapus akun Anda sendiri.');
        }
        if ($u['role'] === 'admin' && $u['status'] === 'ACTIVE') {
            $this->assertOtherActiveAdmin((int) $u['id']);
        }
        $this->db->execute('DELETE FROM users WHERE id = ?', [(int) $u['id']]);
        $this->audit($ctx, 'USER_DELETE', $u['username'], "Akun dihapus: {$u['username']}");
        return [];
    }

    /* ── helper ── */

    /** @return array<string,mixed> */
    private function mustFind(mixed $id): array
    {
        return $this->db->select('SELECT * FROM users WHERE id = ?', [(int) $id])[0] ?? throw new ApiException('Akun tidak ditemukan.');
    }

    private function assertOtherActiveAdmin(int $exceptId): void
    {
        $r = $this->db->select("SELECT COUNT(*) AS c FROM users WHERE role = 'admin' AND status = 'ACTIVE' AND id <> ?", [$exceptId]);
        if ((int) ($r[0]['c'] ?? 0) < 1) {
            throw new ApiException('Harus tersisa minimal satu admin aktif.');
        }
    }

    private function cleanName(mixed $v): string
    {
        $v = trim((string) $v);
        if (mb_strlen($v) < 2 || mb_strlen($v) > 100) {
            throw new ApiException('Nama lengkap 2-100 karakter.');
        }
        return $v;
    }

    private function cleanRole(mixed $v): string
    {
        if (!in_array($v, self::ROLES, true)) {
            throw new ApiException('Role harus security atau admin.');
        }
        return (string) $v;
    }

    /** Security wajib punya unit; admin boleh tanpa unit (bebas berpindah). */
    private function cleanUnit(string $role, mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            if ($role === 'security') {
                throw new ApiException('Akun security wajib memiliki unit (Unit 1 atau Unit 2).');
            }
            return null;
        }
        return $this->units->resolve($raw) ?? throw new ApiException('Unit tidak dikenal.');
    }

    private function cleanPassword(mixed $v): string
    {
        $v = (string) $v;
        if (strlen($v) < self::MIN_PASSWORD) {
            throw new ApiException('Password minimal ' . self::MIN_PASSWORD . ' karakter.');
        }
        return $v;
    }

    /** @param array<string,mixed> $u */
    private function present(array $u): array
    {
        return [
            'id' => (int) $u['id'], 'username' => $u['username'], 'name' => $u['name'], 'role' => $u['role'],
            'unit' => $u['unit_id'], 'unitName' => $u['unit_id'] ? $this->units->name($u['unit_id']) : null,
            'status' => $u['status'], 'createdAt' => $u['created_at'], 'createdBy' => $u['created_by'] ?? null,
            'lastLoginAt' => $u['last_login_at'],
        ];
    }

    private function audit(Context $ctx, string $action, string $ref, string $detail): void
    {
        $unit = $ctx->unit ?? 'U1';
        $this->logs->write($unit, $action, 'USER', $ref, $detail, $ctx->userName());
    }
}
