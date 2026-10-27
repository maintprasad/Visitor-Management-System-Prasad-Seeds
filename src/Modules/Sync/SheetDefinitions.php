<?php

declare(strict_types=1);

namespace Vsm\Modules\Sync;

/**
 * Pemetaan sheet Excel/Google Sheets -> tabel DB.
 * Urutan kolom PERSIS seperti header database_vsm.xlsx, karena panel membaca baris sebagai array berindeks.
 * Tipe: s = teks, n = angka, b = TRUE/FALSE.
 */
final class SheetDefinitions
{
    /** @return array{table:string,unit:string,order:string,columns:list<array{0:string,1:string,2:string}>}|null */
    public static function get(string $sheet): ?array
    {
        return match ($sheet) {
            'Visitors' => [
                'table' => 'visitors', 'unit' => 'unit_id', 'order' => 'created_at DESC', 'version' => 'updated_at',
                'columns' => [
                    ['ID', 'id', 's'], ['VisitorType', 'visitor_type', 's'], ['Nama', 'nama', 's'], ['Company', 'company', 's'],
                    ['Phone', 'phone', 's'], ['Identity', 'identity', 's'], ['Purpose', 'purpose', 's'], ['Host', 'host', 's'],
                    ['Dept', 'dept', 's'], ['Notes', 'notes', 's'], ['Status', 'status', 's'], ['CheckIn', 'check_in', 's'],
                    ['CheckOut', 'check_out', 's'], ['Duration', 'duration', 'n'], ['Area', 'area', 's'],
                    ['JenisPekerjaan', 'jenis_pekerjaan', 's'], ['Personil', 'personil', 'n'], ['SPK', 'spk', 's'],
                    ['StartDate', 'start_date', 's'], ['EndDate', 'end_date', 's'], ['EquipList', 'equip_list', 's'],
                    ['SLAStart', 'sla_start', 's'], ['SLADuration', 'sla_duration', 'n'], ['Plat', 'plat', 's'],
                    ['JenisKendaraan', 'jenis_kendaraan', 's'], ['Cargo', 'cargo', 's'], ['DriverApproved', 'driver_approved', 'b'],
                    ['ApprovedAt', 'approved_at', 's'], ['GatePassOut', 'gate_pass_out', 's'], ['NIK', 'nik', 's'],
                    ['Jabatan', 'jabatan', 's'], ['AuditType', 'audit_type', 's'], ['Scope', 'scope', 's'],
                    ['SuratTugas', 'surat_tugas', 's'], ['CreatedAt', 'created_at', 's'], ['User', 'user_name', 's'], ['Source', 'source', 's'],
                ],
            ],
            'Equipment' => [
                'table' => 'equipment', 'unit' => 'unit_id', 'order' => 'date_in DESC', 'version' => 'updated_at',
                'columns' => [
                    ['ID', 'id', 's'], ['Nama', 'nama', 's'], ['Serial', 'serial', 's'], ['Owner', 'owner', 's'],
                    ['OwnerCompany', 'owner_company', 's'], ['VisitorID', 'visitor_id', 's'], ['VisitorType', 'visitor_type', 's'],
                    ['GatePass', 'gate_pass', 's'], ['Condition', 'item_condition', 's'], ['Qty', 'qty', 'n'], ['Status', 'status', 's'],
                    ['Notes', 'notes', 's'], ['DateIn', 'date_in', 's'], ['DateOut', 'date_out', 's'], ['Source', 'source', 's'], ['User', 'user_name', 's'],
                ],
            ],
            'AbsenKontraktor' => [
                'table' => 'absen_kontraktor', 'unit' => 'unit_id', 'order' => 'tanggal DESC', 'version' => 'updated_at',
                'columns' => [
                    ['ID', 'id', 's'], ['Tanggal', 'tanggal', 's'], ['NamaPT', 'nama_pt', 's'], ['Supervisi', 'supervisi', 's'],
                    ['VisitorID', 'visitor_id', 's'], ['Source', 'source', 's'], ['JumlahPekerja', 'jumlah_pekerja', 'n'],
                    ['NamaPekerja', 'nama_pekerja', 's'], ['JumlahAlat', 'jumlah_alat', 'n'], ['DaftarAlat', 'daftar_alat', 's'], ['User', 'user_name', 's'],
                ],
            ],
            'Logs' => [
                'table' => 'logs', 'unit' => 'unit_id', 'order' => 'id DESC', 'version' => 'id',
                'columns' => [
                    ['Timestamp', 'ts', 's'], ['Action', 'action', 's'], ['Entity', 'entity', 's'],
                    ['RefID', 'ref_id', 's'], ['Detail', 'detail', 's'], ['User', 'user_name', 's'],
                ],
            ],
            default => null,
        };
    }
}
