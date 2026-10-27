<?php

declare(strict_types=1);

namespace Vsm\Modules\Equipment;

use Vsm\Core\Clock;
use Vsm\Core\Ids;
use Vsm\Database\Connection;

final class EquipmentRepository
{
    public function __construct(private readonly Connection $db) {}

    /** @return array<string,mixed>|null */
    public function find(string $unit, string $id): ?array
    {
        return $this->db->select('SELECT * FROM equipment WHERE id = ? AND unit_id = ?', [$id, $unit])[0] ?? null;
    }

    /** Nomor urut gate pass berikutnya: GP-YYYYMMDD-NNN (per hari per unit). */
    public function nextGatePassSeq(string $unit): int
    {
        $prefix = 'GP-' . Clock::localDate() . '-';
        $r = $this->db->select('SELECT COUNT(*) AS c FROM equipment WHERE unit_id = ? AND gate_pass LIKE ?', [$unit, $prefix . '%']);
        return (int) ($r[0]['c'] ?? 0) + 1;
    }

    public static function gatePass(int $seq): string
    {
        return 'GP-' . Clock::localDate() . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string,mixed> $d payload dari frontend (camelCase)
     * @return string id yang tersimpan
     */
    public function insert(string $unit, array $d): string
    {
        $id  = Ids::valid($d['id'] ?? null, 'EQ') && !$this->exists((string) $d['id']) ? (string) $d['id'] : Ids::make('EQ');
        $now = Clock::now();
        $this->db->execute(
            'INSERT INTO equipment (id, unit_id, nama, serial, owner, owner_company, visitor_id, visitor_type, gate_pass,
                item_condition, qty, status, notes, date_in, date_out, source, user_name, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $id, $unit,
                (string) ($d['name'] ?? $d['nama'] ?? ''),
                $d['serial'] ?? null,
                $d['owner'] ?? null,
                $d['ownerCompany'] ?? null,
                ($d['visitorId'] ?? null) ?: null,
                ($d['visitorType'] ?? null) ?: null,
                $d['gatePass'] ?? null,
                $d['condition'] ?? 'Good / Baik',
                max(1, (int) ($d['qty'] ?? 1)),
                $d['status'] ?? 'Inside',
                $d['notes'] ?? null,
                Clock::normalize($d['dateIn'] ?? null) ?? $now,
                Clock::normalize($d['dateOut'] ?? null),
                $d['source'] ?? 'EQUIPMENT_MENU',
                $d['user'] ?? null,
                $now,
            ]
        );
        return $id;
    }

    public function exists(string $id): bool
    {
        return (bool) $this->db->select('SELECT 1 AS x FROM equipment WHERE id = ?', [$id]);
    }

    public function setStatus(string $unit, string $id, string $status, ?string $dateOut): int
    {
        return $this->db->execute(
            'UPDATE equipment SET status = ?, date_out = ?, updated_at = ? WHERE id = ? AND unit_id = ?',
            [$status, $dateOut, Clock::now(), $id, $unit]
        );
    }

    public function delete(string $unit, string $id): int
    {
        return $this->db->execute('DELETE FROM equipment WHERE id = ? AND unit_id = ?', [$id, $unit]);
    }

    public function countByVisitor(string $unit, string $visitorId, string $status): int
    {
        $r = $this->db->select('SELECT COUNT(*) AS c FROM equipment WHERE unit_id = ? AND visitor_id = ? AND status = ?', [$unit, $visitorId, $status]);
        return (int) ($r[0]['c'] ?? 0);
    }

    public function moveByVisitor(string $unit, string $visitorId, string $from, string $to, ?string $dateOut): int
    {
        return $this->db->execute(
            'UPDATE equipment SET status = ?, date_out = ?, updated_at = ? WHERE unit_id = ? AND visitor_id = ? AND status = ?',
            [$to, $dateOut, Clock::now(), $unit, $visitorId, $from]
        );
    }
}
