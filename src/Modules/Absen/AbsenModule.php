<?php

declare(strict_types=1);

namespace Vsm\Modules\Absen;

use Vsm\Core\Access;
use Vsm\Core\Action;
use Vsm\Core\ApiException;
use Vsm\Core\Clock;
use Vsm\Core\Context;
use Vsm\Core\Ids;
use Vsm\Core\Module;
use Vsm\Core\Request;
use Vsm\Database\Connection;

/** Absensi kontraktor (manual, otomatis dari check-in, atau dari self check-in). */
final class AbsenModule implements Module
{
    public function __construct(private readonly Connection $db) {}

    public function actions(): array
    {
        return ['ADD_ABSEN_KONTRAKTOR' => Action::post(Access::Public, $this->add(...))];
    }

    /** @param array<string,mixed> $d */
    private function add(Context $ctx, array $d, Request $req): array
    {
        $unit      = $ctx->requireUnit();
        $visitorId = ($d['visitorId'] ?? null) ?: null;

        if (!$ctx->isAuthenticated()) {
            // App Check-In publik: hanya untuk pendaftaran kontraktor yang baru masuk di unit yang sama.
            $rows = $visitorId
                ? $this->db->select('SELECT status, visitor_type FROM visitors WHERE id = ? AND unit_id = ?', [$visitorId, $unit])
                : [];
            $v = $rows[0] ?? null;
            if (!$v || $v['visitor_type'] !== 'Contractor' || $v['status'] !== 'Waiting Approval') {
                throw new ApiException('Absen hanya bisa dilampirkan pada pendaftaran kontraktor yang menunggu persetujuan.');
            }
            $d['source'] = 'SELF_CHECKIN';
        }

        $id = Ids::valid($d['id'] ?? null, 'ABK') ? (string) $d['id'] : Ids::make('ABK');
        if ($this->db->select('SELECT 1 AS x FROM absen_kontraktor WHERE id = ?', [$id])) {
            return ['id' => $id]; // retry klien
        }
        if ($visitorId && $this->db->select('SELECT 1 AS x FROM absen_kontraktor WHERE visitor_id = ? AND source = ?', [$visitorId, $d['source'] ?? 'MANUAL'])) {
            return ['id' => $id];
        }

        $pekerja = array_values(array_filter(array_map(fn ($p) => trim((string) $p), (array) ($d['pekerja'] ?? []))));
        $alat = [];
        foreach ((array) ($d['alat'] ?? []) as $a) {
            if (is_array($a) && trim((string) ($a['nama'] ?? '')) !== '') {
                $alat[] = trim((string) $a['nama']) . ' (Qty: ' . max(1, (int) ($a['qty'] ?? 1)) . ')';
            }
        }

        $this->db->execute(
            'INSERT INTO absen_kontraktor (id, unit_id, tanggal, nama_pt, supervisi, visitor_id, source, jumlah_pekerja, nama_pekerja, jumlah_alat, daftar_alat, user_name, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $id, $unit,
                Clock::normalize($d['tanggal'] ?? null) ?? Clock::now(),
                (string) ($d['pt'] ?? ''), $d['spv'] ?? null, $visitorId,
                $d['source'] ?? 'MANUAL',
                count($pekerja), implode("\n", $pekerja),
                count($alat), implode("\n", $alat),
                $d['user'] ?? ($ctx->isAuthenticated() ? $ctx->userName() : 'Self Check-In'),
                Clock::now(),
            ]
        );
        return ['id' => $id];
    }
}
