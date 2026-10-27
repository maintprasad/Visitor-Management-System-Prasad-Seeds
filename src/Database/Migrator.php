<?php

declare(strict_types=1);

namespace Vsm\Database;

use Vsm\Core\Clock;

/** Menjalankan database/schema/{mysql|turso}.sql lalu mengisi data dasar (unit). */
final class Migrator
{
    public function __construct(private readonly Connection $db) {}

    /** @return int jumlah statement yang dijalankan */
    public function migrate(): int
    {
        $file = BASE_PATH . '/database/schema/' . $this->db->driver() . '.sql';
        if (!is_file($file)) {
            throw new \RuntimeException("File skema tidak ada: {$file}");
        }

        $lines = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
            // Komentar penuh maupun di ujung baris dibuang (skema tidak memakai '--' di dalam string).
            $line = preg_replace('/\s*--.*$/', '', $line) ?? $line;
            if (trim($line) !== '') {
                $lines[] = $line;
            }
        }
        $count = 0;
        foreach (explode(';', implode("\n", $lines)) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt !== '') {
                $this->db->execute($stmt);
                $count++;
            }
        }

        $this->seedUnits();
        return $count;
    }

    private function seedUnits(): void
    {
        foreach (['U1' => 'Unit 1 Pasuruan', 'U2' => 'Unit 2 Pasuruan'] as $id => $name) {
            $exists = $this->db->select('SELECT id FROM units WHERE id = ?', [$id]);
            if (!$exists) {
                $this->db->execute('INSERT INTO units (id, name, status) VALUES (?, ?, ?)', [$id, $name, 'ACTIVE']);
            }
        }
    }

    /** Dipakai bin/create-user.php. */
    public static function nowIso(): string
    {
        return Clock::now();
    }
}
