<?php

declare(strict_types=1);

namespace Vsm\Modules\Visitors;

use Vsm\Core\ApiException;
use Vsm\Core\Clock;
use Vsm\Core\Context;
use Vsm\Core\Ids;
use Vsm\Database\Connection;
use Vsm\Modules\Equipment\EquipmentRepository;
use Vsm\Modules\Logs\LogRepository;
use Vsm\Modules\Units\UnitRepository;

/** Aturan bisnis kunjungan: check-in, check-out, persetujuan, re-check-in, driver. */
final class VisitorService
{
    public const TYPES = ['Guest', 'Contractor', 'Driver', 'Employee Test', 'Auditor'];
    public const DRIVER_VEHICLES = ['Wing Box', 'Container', 'Colt Diesel', 'Pick Up'];
    private const STATUSES = ['Waiting Approval', 'Inside', 'Overstay', 'Checked Out', 'Break', 'Rejected'];

    public function __construct(
        private readonly Connection $db,
        private readonly EquipmentRepository $equipment,
        private readonly LogRepository $logs,
        private readonly UnitRepository $units,
    ) {}

    /** @return array<string,mixed>|null */
    public function find(string $unit, string $id): ?array
    {
        return $this->db->select('SELECT * FROM visitors WHERE id = ? AND unit_id = ?', [$id, $unit])[0] ?? null;
    }

    /** @return array<string,mixed>|null */
    public function findAnyUnit(string $id): ?array
    {
        return $this->db->select('SELECT * FROM visitors WHERE id = ?', [$id])[0] ?? null;
    }

    /* ───────────────────────── CHECK-IN ───────────────────────── */

    /**
     * Pendaftaran baru (app Check-In publik => selalu 'Waiting Approval'),
     * atau aktivasi visitor yang sudah terdaftar (panel scan QR => 'Inside').
     *
     * @param array<string,mixed> $d
     * @return array<string,mixed>
     */
    public function checkin(Context $ctx, array $d): array
    {
        $unit = $ctx->requireUnit();
        $id   = (string) ($d['id'] ?? '');
        $auth = $ctx->isAuthenticated();

        $existing = Ids::valid($id, 'VIS') ? $this->findAnyUnit($id) : null;
        if ($existing) {
            if ($existing['unit_id'] !== $unit) {
                throw new ApiException('ID visitor ini sudah dipakai di unit lain.');
            }
            if (!$auth) {
                return ['id' => $id, 'status' => $existing['status'], 'existing' => true]; // retry app publik
            }
            return $this->activate($ctx, $existing, $d);
        }

        $type = (string) ($d['visitorType'] ?? 'Guest');
        if (!in_array($type, self::TYPES, true)) {
            throw new ApiException('Tipe visitor tidak valid.');
        }
        $name = trim((string) ($d['name'] ?? ''));
        if ($name === '') {
            throw new ApiException('Nama visitor wajib diisi.');
        }

        if ($type === 'Driver') {
            // Driver: Nama Driver, Nama Transporter (kolom company), Nomor Polisi, Jenis Kendaraan.
            if (trim((string) ($d['company'] ?? '')) === '' || $d['company'] === '—') {
                throw new ApiException('Nama transporter wajib diisi.');
            }
            if (trim((string) ($d['plate'] ?? '')) === '') {
                throw new ApiException('Nomor polisi kendaraan wajib diisi.');
            }
            if (!in_array($d['vehicle'] ?? '', self::DRIVER_VEHICLES, true)) {
                throw new ApiException('Jenis kendaraan harus salah satu: ' . implode(', ', self::DRIVER_VEHICLES) . '.');
            }
            $d['plate'] = strtoupper(trim((string) $d['plate']));
        }

        $id     = Ids::valid($id, 'VIS') ? $id : Ids::make('VIS');
        $now    = Clock::now();
        // Driver langsung Inside saat check-in (tanpa menunggu approval); tipe lain menunggu security.
        $status = $type === 'Driver' ? 'Inside' : 'Waiting Approval';
        if ($auth) {
            $status = in_array($d['status'] ?? '', self::STATUSES, true) ? (string) $d['status'] : 'Inside';
        }
        $checkIn = $auth ? (Clock::normalize($d['checkIn'] ?? null) ?? $now) : $now;

        $equipList = $this->normalizeEquipList($d['equipList'] ?? []);
        $names     = array_values(array_filter(array_map('trim', array_map('strval', (array) ($d['pekerjaList'] ?? $d['namaList'] ?? [])))));
        $notes     = $this->composeNotes((string) ($d['notes'] ?? ''), $names, $type);
        $purpose   = (string) ($d['purpose'] ?? '');
        $slaStart  = ($type === 'Contractor' && $status === 'Inside')
            ? (Clock::normalize($d['slaStart'] ?? null) ?? $checkIn)
            : null;
        $userName  = $auth ? ($d['user'] ?? $ctx->userName()) : $name;

        $row = [
            'id' => $id, 'unit_id' => $unit, 'visitor_type' => $type, 'nama' => $name,
            'company' => $d['company'] ?? null, 'phone' => $d['phone'] ?? null, 'identity' => $d['identity'] ?? null,
            'purpose' => $purpose, 'host' => $d['host'] ?? null, 'dept' => $d['dept'] ?? null, 'notes' => $notes,
            'status' => $status, 'check_in' => $checkIn, 'check_out' => null, 'duration' => null,
            'area' => $d['area'] ?? null,
            'jenis_pekerjaan' => in_array($type, ['Contractor', 'Driver'], true) ? $purpose : null,
            'personil' => $type === 'Contractor' ? (int) ($d['personil'] ?? count($names)) : null,
            'spk' => $d['spk'] ?? null,
            'start_date' => ($d['startDate'] ?? null) ?: null, 'end_date' => ($d['endDate'] ?? null) ?: null,
            'equip_list' => $this->serializeEquipList($equipList),
            'sla_start' => $slaStart, 'sla_duration' => null,
            'plat' => $d['plate'] ?? null, 'jenis_kendaraan' => $d['vehicle'] ?? null, 'cargo' => $d['cargo'] ?? null,
            'driver_approved' => 0, 'approved_at' => null, 'gate_pass_out' => null,
            'nik' => $d['nik'] ?? null, 'jabatan' => $d['jabatan'] ?? null,
            'audit_type' => $d['auditType'] ?? null, 'scope' => $d['scope'] ?? null,
            'surat_tugas' => $d['suratTugas'] ?? $d['surat'] ?? null,
            'nama_personil' => $names ? implode("\n", $names) : null,
            'created_at' => $now, 'updated_at' => $now,
            'user_name' => $userName,
            'source' => $d['source'] ?? ($auth ? 'WEB' : 'SELF_REGISTER'),
        ];

        $cols = array_keys($row);
        $this->db->execute(
            'INSERT INTO visitors (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
            array_values($row)
        );

        $registered = $this->registerEquipment($unit, $row, $equipList);

        if (!$auth) {
            $this->logs->write($unit, 'SELF_REGISTER', 'VISITOR', $id, "[{$type}] {$name} — {$purpose} — Self-registration", $name);
        }
        return ['id' => $id, 'status' => $status, 'unit' => $unit, 'equipmentRegistered' => $registered];
    }

    /** Panel menyetujui / meng-check-in visitor yang sudah mendaftar sendiri. */
    private function activate(Context $ctx, array $v, array $d): array
    {
        $status = in_array($d['status'] ?? '', self::STATUSES, true) ? (string) $d['status'] : 'Inside';
        if ($v['status'] === $status) {
            return ['id' => $v['id'], 'status' => $status, 'existing' => true];
        }
        $this->changeStatus($ctx, $v, $status, Clock::normalize($d['checkIn'] ?? null));
        return ['id' => $v['id'], 'status' => $status];
    }

    /* ───────────────────────── STATUS ───────────────────────── */

    /**
     * Ubah status visitor, termasuk efek sampingnya (SLA kontraktor, re-check-in, alat terkait).
     *
     * @param array<string,mixed> $v baris visitors saat ini
     */
    public function changeStatus(Context $ctx, array $v, string $status, ?string $checkIn = null): void
    {
        $unit = $v['unit_id'];
        $now  = Clock::now();
        $set  = ['status' => $status, 'updated_at' => $now];

        if ($status === 'Inside') {
            if ($v['status'] === 'Checked Out') {
                // Re-check-in: timer dilanjutkan dari waktu check-out terakhir.
                $set['check_in'] = $v['check_out'] ?: $v['check_in'];
                $this->equipment->moveByVisitor($unit, $v['id'], 'Outside', 'Inside', null);
            } elseif ($v['status'] === 'Waiting Approval') {
                $set['check_in'] = $checkIn ?? $now;
            }
            $set['check_out'] = null;
            $set['duration']  = null;
            if ($v['visitor_type'] === 'Contractor' && !$v['sla_start']) {
                $set['sla_start'] = $now;
            }
            $this->equipment->moveByVisitor($unit, $v['id'], 'Pending Approval', 'Inside', null);
        }

        $assign = implode(', ', array_map(fn ($c) => "{$c} = ?", array_keys($set)));
        $this->db->execute("UPDATE visitors SET {$assign} WHERE id = ? AND unit_id = ?", [...array_values($set), $v['id'], $unit]);
    }

    /** @param array<string,mixed> $d */
    public function updateStatus(Context $ctx, array $d): array
    {
        $unit   = $ctx->requireUnit();
        $sheet  = (string) ($d['sheet'] ?? 'Visitors');
        $id     = (string) ($d['id'] ?? '');
        $status = trim((string) ($d['status'] ?? ''));
        if ($id === '' || $status === '' || strlen($status) > 30) {
            throw new ApiException('Data update status tidak lengkap.');
        }

        if ($sheet === 'Equipment') {
            $eq = $this->equipment->find($unit, $id) ?? throw new ApiException('Equipment tidak ditemukan di unit ini.');
            $this->equipment->setStatus($unit, $id, $status, $status === 'Outside' ? ($eq['date_out'] ?: Clock::now()) : null);
            return ['id' => $id];
        }

        $v = $this->find($unit, $id) ?? throw new ApiException('Visitor tidak ditemukan di unit ini.');
        if (!in_array($status, self::STATUSES, true)) {
            throw new ApiException('Status tidak valid: ' . $status);
        }
        if ($status === 'Checked Out') {
            return $this->checkout($ctx, ['id' => $id]);
        }
        $this->changeStatus($ctx, $v, $status);
        return ['id' => $id];
    }

    /* ───────────────────────── CHECK-OUT ───────────────────────── */

    /**
     * @param array<string,mixed> $d
     * @return array<string,mixed>
     */
    public function checkout(Context $ctx, array $d): array
    {
        $id = (string) ($d['id'] ?? '');
        $v  = $this->findAnyUnit($id) ?? throw new ApiException('Visitor ID tidak ditemukan.');

        if ($ctx->unit !== null && $ctx->unit !== $v['unit_id']) {
            throw new ApiException('Visitor ini terdaftar di ' . $this->units->name($v['unit_id']) . ', bukan ' . $this->units->name($ctx->unit) . '.');
        }
        $unit = $v['unit_id'];
        $auth = $ctx->isAuthenticated();

        if ($v['status'] === 'Checked Out') {
            return ['id' => $id, 'checkOut' => $v['check_out'], 'already' => true];
        }
        if (!$auth && $v['status'] === 'Waiting Approval') {
            throw new ApiException('Visitor belum disetujui security, belum bisa check-out.');
        }

        $out = $auth ? (Clock::normalize($d['checkOutTime'] ?? $d['checkOut'] ?? null) ?? Clock::now()) : Clock::now();
        $duration = Clock::daysBetween($v['check_in'], $out);
        $sla      = $v['sla_start'] ? Clock::daysBetween($v['sla_start'], $out) : null;

        $this->db->execute(
            "UPDATE visitors SET status = 'Checked Out', check_out = ?, duration = ?, sla_duration = ?, updated_at = ? WHERE id = ? AND unit_id = ?",
            [$out, $duration, $sla, Clock::now(), $id, $unit]
        );

        if (!$auth) {
            $this->logs->write($unit, 'CHECK_OUT', 'VISITOR', $id, "[{$v['visitor_type']}] {$v['nama']} — check-out mandiri (app Check-Out)", $v['nama']);
        }
        return [
            'id' => $id, 'checkOut' => $out, 'duration' => $duration,
            // Alat kontraktor diverifikasi petugas di panel; app publik hanya memberi tahu.
            'equipInside' => $this->equipment->countByVisitor($unit, $id, 'Inside'),
        ];
    }

    /* ───────────────────────── HAPUS & DRIVER ───────────────────────── */

    public function delete(Context $ctx, string $id): void
    {
        if ($this->db->execute('DELETE FROM visitors WHERE id = ? AND unit_id = ?', [$id, $ctx->requireUnit()]) === 0) {
            throw new ApiException('Data tidak ditemukan di unit ini.');
        }
    }

    /** @param array<string,mixed> $d */
    public function approveDriverOut(Context $ctx, array $d): array
    {
        $v = $this->mustFindDriver($ctx, (string) ($d['id'] ?? ''));
        $this->db->execute(
            'UPDATE visitors SET driver_approved = 1, approved_at = ?, updated_at = ? WHERE id = ? AND unit_id = ?',
            [Clock::normalize($d['approvedAt'] ?? null) ?? Clock::now(), Clock::now(), $v['id'], $v['unit_id']]
        );
        return ['id' => $v['id']];
    }

    /** @param array<string,mixed> $d */
    public function setDriverGatePass(Context $ctx, array $d): array
    {
        $v = $this->mustFindDriver($ctx, (string) ($d['id'] ?? ''));
        if (!(int) $v['driver_approved']) {
            throw new ApiException('Driver belum mendapat approval keluar.');
        }
        $gp = trim((string) ($d['gatePassOut'] ?? ''));
        if ($gp === '') {
            throw new ApiException('Nomor gate pass kosong.');
        }
        $this->db->execute('UPDATE visitors SET gate_pass_out = ?, updated_at = ? WHERE id = ? AND unit_id = ?', [$gp, Clock::now(), $v['id'], $v['unit_id']]);
        return ['id' => $v['id']];
    }

    private function mustFindDriver(Context $ctx, string $id): array
    {
        $v = $this->find($ctx->requireUnit(), $id) ?? throw new ApiException('Driver tidak ditemukan di unit ini.');
        if ($v['visitor_type'] !== 'Driver') {
            throw new ApiException('Visitor ini bukan Driver.');
        }
        return $v;
    }

    /* ───────────────────────── PUBLIK ───────────────────────── */

    /** Data minimal untuk app Check-In (polling status) & Check-Out (hasil scan). Tanpa NIK/identitas. */
    public function presentPublic(array $v, bool $withEquip = true): array
    {
        $names = $v['nama_personil'] ? array_values(array_filter(explode("\n", (string) $v['nama_personil']))) : [];
        return [
            'id' => $v['id'], 'unit' => $v['unit_id'], 'unitName' => $this->units->name($v['unit_id']),
            'visitorType' => $v['visitor_type'], 'name' => $v['nama'], 'company' => $v['company'], 'phone' => $v['phone'],
            'purpose' => $v['purpose'], 'host' => $v['host'], 'dept' => $v['dept'],
            'plate' => $v['plat'], 'vehicle' => $v['jenis_kendaraan'],
            'status' => $v['status'], 'checkIn' => $v['check_in'], 'checkOut' => $v['check_out'],
            'namaList' => $names,
            'equipInside' => $withEquip ? $this->equipment->countByVisitor($v['unit_id'], $v['id'], 'Inside') : null,
        ];
    }

    /** @return list<array<string,mixed>> */
    public function listInside(string $unit): array
    {
        $rows = $this->db->select(
            "SELECT * FROM visitors WHERE unit_id = ? AND status IN ('Inside', 'Overstay') ORDER BY check_in DESC LIMIT 200",
            [$unit]
        );
        return array_map(fn ($r) => $this->presentPublic($r, false), $rows);
    }

    /* ───────────────────────── HELPER ───────────────────────── */

    /** @return list<array{nama:string,qty:string}> */
    private function normalizeEquipList(mixed $raw): array
    {
        $out = [];
        foreach ((array) $raw as $item) {
            $nama = is_array($item) ? trim((string) ($item['nama'] ?? '')) : trim((string) $item);
            if ($nama !== '') {
                $out[] = ['nama' => $nama, 'qty' => (string) max(1, (int) (is_array($item) ? ($item['qty'] ?? 1) : 1))];
            }
        }
        return $out;
    }

    /** "Kating;Gerinda(3)" — qty 1 tidak ditulis, sama seperti data Excel. */
    private function serializeEquipList(array $list): ?string
    {
        if (!$list) {
            return null;
        }
        return implode(';', array_map(fn ($e) => $e['qty'] === '1' ? $e['nama'] : "{$e['nama']}({$e['qty']})", $list));
    }

    /** @param list<string> $names */
    private function composeNotes(string $notes, array $names, string $type): string
    {
        $notes = trim($notes);
        if (!$names || !in_array($type, ['Contractor', 'Guest', 'Auditor'], true)) {
            return $notes;
        }
        $list = implode("\n", array_map(fn ($n, $i) => ($i + 1) . ' ' . $n, $names, array_keys($names)));
        return $notes === '' ? $list : $notes . "\n\nDaftar nama:\n" . $list;
    }

    /**
     * Alat yang dibawa saat check-in otomatis masuk ke menu Equipment (seperti versi Google Sheets).
     *
     * @param array<string,mixed> $visit
     * @param list<array{nama:string,qty:string}> $items
     */
    private function registerEquipment(string $unit, array $visit, array $items): int
    {
        if (!$items) {
            return 0;
        }
        $seq    = $this->equipment->nextGatePassSeq($unit);
        $status = $visit['status'] === 'Waiting Approval' ? 'Pending Approval' : 'Inside';
        foreach ($items as $item) {
            $this->equipment->insert($unit, [
                'name' => $item['nama'], 'qty' => $item['qty'],
                'owner' => $visit['nama'], 'ownerCompany' => $visit['company'],
                'visitorId' => $visit['id'], 'visitorType' => $visit['visitor_type'],
                'gatePass' => EquipmentRepository::gatePass($seq++),
                'condition' => 'Good / Baik', 'status' => $status,
                'notes' => 'Auto-registered dari check-in ' . $visit['visitor_type'],
                'dateIn' => $visit['check_in'],
                'source' => strtoupper(str_replace(' ', '_', $visit['visitor_type'])) . '_CHECKIN',
                'user' => $visit['user_name'],
            ]);
        }
        return count($items);
    }
}
