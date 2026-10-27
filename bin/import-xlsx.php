<?php

declare(strict_types=1);

/*
 * Impor data lama dari database_vsm.xlsx ke database aktif (mysql / turso).
 *   php bin/import-xlsx.php ../database_vsm.xlsx --unit=U1 [--wipe]
 * - Hanya sheet Visitors, Equipment, AbsenKontraktor, Logs (Contractors & Drivers = turunan Visitors).
 * - Semua baris diberi unit yang dipilih (--unit). Baris dengan ID yang sudah ada dilewati.
 * - --wipe : hapus dulu data visitors/equipment/absen/logs MILIK UNIT ITU sebelum impor.
 * Tanpa Composer: xlsx dibaca langsung lewat ZipArchive + SimpleXML.
 */

require dirname(__DIR__) . '/bootstrap.php';

use Vsm\Database\Connection;
use Vsm\Database\Database;
use Vsm\Modules\Sync\SheetDefinitions;
use Vsm\Modules\Units\UnitRepository;

$file = $argv[1] ?? '';
$opt  = [];
foreach (array_slice($argv, 2) as $a) {
    if (preg_match('/^--([a-z]+)(?:=(.*))?$/s', $a, $m)) {
        $opt[$m[1]] = $m[2] ?? true;
    }
}
if (!is_file($file)) {
    fwrite(STDERR, "Pakai: php bin/import-xlsx.php <file.xlsx> --unit=U1|U2 [--wipe]\n");
    exit(1);
}

$db   = Database::connection();
$unit = (new UnitRepository($db))->resolve($opt['unit'] ?? '') ?? null;
if ($unit === null) {
    fwrite(STDERR, "--unit wajib (U1 atau U2) dan harus sudah ada (jalankan bin/migrate.php).\n");
    exit(1);
}

/* ───────── baca xlsx ───────── */

/** @return array<string,list<list<string|null>>> nama sheet => baris */
function readWorkbook(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        throw new RuntimeException('Tidak bisa membuka xlsx.');
    }
    $xml = fn (string $name) => ($s = $zip->getFromName($name)) === false ? null : simplexml_load_string($s);

    $shared = [];
    if ($ss = $xml('xl/sharedStrings.xml')) {
        foreach ($ss->si as $si) {
            $t = '';
            if (isset($si->t)) {
                $t = (string) $si->t;
            } else {
                foreach ($si->r as $r) {
                    $t .= (string) $r->t;
                }
            }
            $shared[] = $t;
        }
    }

    $wb   = $xml('xl/workbook.xml');
    $rels = [];
    foreach ($xml('xl/_rels/workbook.xml.rels')->Relationship as $r) {
        $rels[(string) $r['Id']] = (string) $r['Target'];
    }

    $out = [];
    foreach ($wb->sheets->sheet as $sh) {
        $rid    = (string) $sh->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        $target = ltrim($rels[$rid], '/');
        $sheet  = $xml(str_starts_with($target, 'xl/') ? $target : 'xl/' . $target);
        $rows   = [];
        foreach ($sheet->sheetData->row as $row) {
            $line = [];
            foreach ($row->c as $c) {
                preg_match('/^([A-Z]+)/', (string) $c['r'], $m);
                $idx = 0;
                foreach (str_split($m[1]) as $ch) {
                    $idx = $idx * 26 + (ord($ch) - 64);
                }
                $idx--;
                $t = (string) $c['t'];
                $v = match ($t) {
                    's' => $shared[(int) $c->v] ?? null,
                    'inlineStr' => (string) $c->is->t,
                    'b' => ((string) $c->v) === '1' ? 'TRUE' : 'FALSE',
                    default => isset($c->v) ? (string) $c->v : null,
                };
                $line[$idx] = [$t, $v];
            }
            $rows[] = $line;
        }
        $out[(string) $sh['name']] = $rows;
    }
    $zip->close();
    return $out;
}

/** Angka besar dari Excel (mis. NIK) jangan jadi notasi ilmiah. */
function plain(?string $v): ?string
{
    if ($v === null || $v === '') {
        return null;
    }
    if (is_numeric($v) && (str_contains($v, 'E') || str_contains($v, 'e'))) {
        return sprintf('%.0f', (float) $v);
    }
    if (is_numeric($v) && str_contains($v, '.') && (float) $v == floor((float) $v)) {
        return (string) (int) $v;
    }
    return $v;
}

/** Angka dari sel; teks campuran (mis. '3 orang' + daftar nama) -> angka di depan, teks asli disimpan di _raw. */
function self_num(?string $raw, array &$rec, string $col): int|float|null
{
    if ($raw === null || $raw === '') {
        return null;
    }
    if (is_numeric($raw)) {
        return $raw + 0;
    }
    $rec['_raw'][$col] = $raw;
    return preg_match('/^\s*(\d+)/', $raw, $m) ? (int) $m[1] : null;
}

function importSheet(Connection $db, string $unit, string $sheet, array $rows, array $extra, bool $skipExisting, callable $fix): array
{
    $def = SheetDefinitions::get($sheet);
    $pos = []; // nama header -> indeks kolom
    foreach ($rows[0] ?? [] as $idx => $cell) {
        $pos[(string) $cell[1]] = $idx;
    }

    $existing = [];
    if ($skipExisting) {
        foreach ($db->select("SELECT id FROM {$def['table']}") as $r) {
            $existing[$r['id']] = true;
        }
    }

    $colNames = [];
    $stmts    = [];
    $ins = $skip = 0;
    foreach (array_slice($rows, 1) as $line) {
        $rec = ['unit_id' => $unit];
        foreach ($def['columns'] as [$header, $col, $type]) {
            $cell = isset($pos[$header]) ? ($line[$pos[$header]] ?? null) : null;
            $raw  = $cell[1] ?? null;
            $rec[$col] = match ($type) {
                'n' => self_num($raw, $rec, $col),
                'b' => in_array(strtoupper((string) $raw), ['TRUE', '1'], true) ? 1 : 0,
                default => plain($raw),
            };
        }
        $rec = $fix($rec);
        if ($rec !== null) {
            unset($rec['_raw']);
        }
        if ($rec === null || ($skipExisting && isset($existing[$rec['id'] ?? '']))) {
            $skip++;
            continue;
        }
        $rec += $extra;
        if ($skipExisting) {
            $existing[$rec['id']] = true; // ID ganda di dalam file yang sama
        }
        $colNames = array_keys($rec);
        $stmts[] = ['INSERT INTO ' . $def['table'] . ' (' . implode(', ', $colNames) . ') VALUES (' . implode(', ', array_fill(0, count($colNames), '?')) . ')', array_values($rec)];
        $ins++;
        if (count($stmts) >= 100) {
            $db->batch($stmts);
            $stmts = [];
        }
    }
    if ($stmts) {
        $db->batch($stmts);
    }
    return [$ins, $skip];
}

/* ───────── jalankan ───────── */

$book = readWorkbook($file);
echo "Sheet ditemukan: " . implode(', ', array_keys($book)) . "\n";

if (!empty($opt['wipe'])) {
    foreach (['logs', 'absen_kontraktor', 'equipment', 'visitors'] as $t) {
        $db->execute("DELETE FROM {$t} WHERE unit_id = ?", [$unit]);
    }
    echo "Data lama unit {$unit} dihapus (--wipe).\n";
}

// Excel menyimpan nomor telepon sebagai angka sehingga angka 0 di depan hilang (85733070165 -> 0857...).
$fixPhone = static fn (?string $p): ?string => ($p !== null && ctype_digit($p) && $p[0] !== '0' && $p[0] === '8') ? '0' . $p : $p;

$report = [];

if (isset($book['Visitors'])) {
    $report['Visitors'] = importSheet($db, $unit, 'Visitors', $book['Visitors'], [], true, function (array $r) use ($fixPhone) {
        if (!$r['id'] || !$r['nama']) {
            return null;
        }
        $r['phone']      = $fixPhone($r['phone']);
        // Driver: nama jenis kendaraan lama "Pickup" -> "Pick Up" (daftar baru: Wing Box, Container, Colt Diesel, Pick Up).
        if ($r['visitor_type'] === 'Driver' && $r['jenis_kendaraan'] === 'Pickup') {
            $r['jenis_kendaraan'] = 'Pick Up';
        }
        $r['status']     = $r['status'] ?: 'Checked Out';
        $r['check_in']   = $r['check_in'] ?: ($r['created_at'] ?: gmdate('Y-m-d\TH:i:s.000\Z'));
        $r['created_at'] = $r['created_at'] ?: $r['check_in'];
        $r['updated_at'] = $r['check_out'] ?: $r['created_at'];
        // Sel Personil kadang berisi teks seperti '3 orang' + daftar nama per baris -> pecah jadi jumlah + nama.
        $names = [];
        foreach (explode("\n", (string) ($r['_raw']['personil'] ?? '')) as $i => $ln) {
            $ln = trim(preg_replace('/^\d+[.)]\s*/', '', trim($ln)));
            if ($i > 0 && $ln !== '') {
                $names[] = $ln;
            }
        }
        $r['nama_personil'] = $names ? implode("\n", $names) : null;
        return $r;
    });
}
if (isset($book['Equipment'])) {
    $report['Equipment'] = importSheet($db, $unit, 'Equipment', $book['Equipment'], [], true, function (array $r) {
        if (!$r['id'] || !$r['nama']) {
            return null;
        }
        $r['qty']        = max(1, (int) $r['qty']);
        $r['status']     = $r['status'] ?: 'Inside';
        $r['updated_at'] = $r['date_out'] ?: ($r['date_in'] ?: gmdate('Y-m-d\TH:i:s.000\Z'));
        return $r;
    });
}
if (isset($book['AbsenKontraktor'])) {
    $report['AbsenKontraktor'] = importSheet($db, $unit, 'AbsenKontraktor', $book['AbsenKontraktor'], [], true, function (array $r) {
        if (!$r['id']) {
            return null;
        }
        $r['nama_pt']        = $r['nama_pt'] ?? '';
        $r['tanggal']        = $r['tanggal'] ?: gmdate('Y-m-d\TH:i:s.000\Z');
        $r['jumlah_pekerja'] = (int) $r['jumlah_pekerja'];
        $r['jumlah_alat']    = (int) $r['jumlah_alat'];
        $r['updated_at']     = $r['tanggal'];
        return $r;
    });
}
if (isset($book['Logs'])) {
    $has = (int) ($db->select('SELECT COUNT(*) AS c FROM logs WHERE unit_id = ?', [$unit])[0]['c'] ?? 0);
    if ($has > 0) {
        echo "Logs: dilewati (unit {$unit} sudah punya {$has} log; pakai --wipe untuk impor ulang).\n";
    } else {
        $report['Logs'] = importSheet($db, $unit, 'Logs', $book['Logs'], [], false, function (array $r) {
            return $r['ts'] && $r['action'] ? $r : null;
        });
    }
}

foreach ($report as $sheet => [$ins, $skip]) {
    printf("  %-16s masuk: %5d   dilewati: %d\n", $sheet, $ins, $skip);
}
echo "Selesai. Semua data diimpor ke {$unit}.\n";
