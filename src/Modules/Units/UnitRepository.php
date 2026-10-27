<?php

declare(strict_types=1);

namespace Vsm\Modules\Units;

use Vsm\Database\Connection;

final class UnitRepository
{
    public function __construct(private readonly Connection $db) {}

    /** @return list<array{id:string,name:string}> */
    public function active(): array
    {
        return $this->db->select("SELECT id, name FROM units WHERE status = 'ACTIVE' ORDER BY id");
    }

    /** Terima 'U1', 'u1', '1', 'unit 1', 'unit1' -> 'U1'; unit tak dikenal -> null. */
    public function resolve(mixed $raw): ?string
    {
        if (!is_string($raw) && !is_int($raw)) {
            return null;
        }
        if (!preg_match('/(\d+)\s*$/', (string) $raw, $m)) {
            return null;
        }
        $id = 'U' . (int) $m[1];
        $hit = $this->db->select("SELECT id FROM units WHERE id = ? AND status = 'ACTIVE'", [$id]);
        return $hit ? $id : null;
    }

    public function name(string $id): string
    {
        $r = $this->db->select('SELECT name FROM units WHERE id = ?', [$id]);
        return (string) ($r[0]['name'] ?? $id);
    }
}
