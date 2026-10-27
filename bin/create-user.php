<?php

declare(strict_types=1);

/*
 * Buat / reset akun panel.
 *   php bin/create-user.php --username=sec.u1 --name="Security Unit 1" --unit=U1 [--password=...] [--role=security|admin]
 * Tanpa --password, dibuatkan password acak dan ditampilkan SEKALI.
 * role=admin boleh berpindah unit; role=security terkunci ke satu unit.
 */

require dirname(__DIR__) . '/bootstrap.php';

use Vsm\Core\Clock;
use Vsm\Database\Database;
use Vsm\Modules\Units\UnitRepository;

$opt = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z]+)=(.*)$/s', $a, $m)) {
        $opt[$m[1]] = $m[2];
    }
}

$username = trim($opt['username'] ?? '');
$role     = $opt['role'] ?? 'security';
if ($username === '' || !in_array($role, ['security', 'admin'], true)) {
    fwrite(STDERR, "Pakai: php bin/create-user.php --username=... --name=... --unit=U1|U2 [--password=...] [--role=security|admin]\n");
    exit(1);
}

$db    = Database::connection();
$units = new UnitRepository($db);
$unit  = isset($opt['unit']) ? $units->resolve($opt['unit']) : null;
if ($role === 'security' && $unit === null) {
    fwrite(STDERR, "Akun security wajib punya --unit=U1 atau U2 (jalankan bin/migrate.php dulu).\n");
    exit(1);
}

$generated = !isset($opt['password']) || $opt['password'] === '';
$password  = $generated ? rtrim(strtr(base64_encode(random_bytes(9)), '+/', 'Ab'), '=') : $opt['password'];
$hash      = password_hash($password, PASSWORD_DEFAULT);
$name      = $opt['name'] ?? $username;

$exists = $db->select('SELECT id FROM users WHERE username = ?', [$username]);
if ($exists) {
    $db->execute('UPDATE users SET password_hash = ?, name = ?, role = ?, unit_id = ?, status = ? WHERE username = ?', [$hash, $name, $role, $unit, 'ACTIVE', $username]);
    echo "Akun diperbarui: {$username}\n";
} else {
    $db->execute(
        'INSERT INTO users (username, password_hash, name, role, unit_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$username, $hash, $name, $role, $unit, 'ACTIVE', Clock::now()]
    );
    echo "Akun dibuat: {$username}\n";
}
echo "  role={$role}  unit=" . ($unit ?? '-') . "\n";
if ($generated) {
    echo "  password={$password}   <- catat sekarang, tidak disimpan di tempat lain\n";
}
