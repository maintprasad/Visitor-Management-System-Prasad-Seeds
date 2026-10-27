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

        $count += $this->upgradeExistingTables();
        $this->seedUnits();
        return $count;
    }

    /**
     * Skema lama -> baru untuk tabel yang sudah terlanjur dibuat.
     * Tiap kolom diperiksa dulu, jadi aman dijalankan berulang.
     */
    private function upgradeExistingTables(): int
    {
        $added = 0;
        $columns = [
            ['users', 'created_by', 'VARCHAR(60) NULL', 'TEXT NULL'],
            ['users', 'updated_at', 'VARCHAR(30) NULL', 'TEXT NULL'],
        ];
        foreach ($columns as [$table, $column, $mysqlType, $tursoType]) {
            if ($this->hasColumn($table, $column)) {
                continue;
            }
            $type = $this->db->driver() === 'mysql' ? $mysqlType : $tursoType;
            $this->db->execute("ALTER TABLE {$table} ADD COLUMN {$column} {$type}");
            $added++;
        }
        return $added;
    }

    /** $table dan $column selalu konstanta dari kode ini (bukan input pengguna). */
    private function hasColumn(string $table, string $column): bool
    {
        if ($this->db->driver() === 'mysql') {
            return (bool) $this->db->select(
                'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, $column]
            );
        }
        foreach ($this->db->select("SELECT name FROM pragma_table_info('{$table}')") as $row) {
            if ($row['name'] === $column) {
                return true;
            }
        }
        return false;
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
