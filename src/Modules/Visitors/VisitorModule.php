<?php

declare(strict_types=1);

namespace Vsm\Modules\Visitors;

use Vsm\Core\Access;
use Vsm\Core\Action;
use Vsm\Core\ApiException;
use Vsm\Core\Context;
use Vsm\Core\Module;
use Vsm\Core\Request;
use Vsm\Database\Connection;
use Vsm\Modules\Equipment\EquipmentRepository;

final class VisitorModule implements Module
{
    public function __construct(
        private readonly VisitorService $visitors,
        private readonly EquipmentRepository $equipment,
        private readonly Connection $db,
    ) {}

    public function actions(): array
    {
        $checkin = fn (Context $c, array $d) => $this->visitors->checkin($c, $d);
        return [
            // App Check-In (publik) & panel
            'CHECKIN_VISITOR' => Action::post(Access::Public, $checkin),
            'SELF_REGISTER'   => Action::post(Access::Public, $checkin),
            // App Check-Out (publik) & panel
            'CHECKOUT_VISITOR' => Action::post(Access::Public, fn (Context $c, array $d) => $this->visitors->checkout($c, $d)),
            'GET_VISITOR'      => Action::get(Access::Public, $this->getVisitor(...)),
            'GET_ACTIVE_VISITORS' => Action::get(Access::Public, $this->activeVisitors(...)),
            // Panel
            'UPDATE_STATUS'    => Action::post(Access::Auth, fn (Context $c, array $d) => $this->visitors->updateStatus($c, $d)),
            'DELETE_ROW'       => Action::post(Access::Auth, $this->deleteRow(...)),
            'APPROVE_DRIVER_OUT'       => Action::post(Access::Auth, fn (Context $c, array $d) => $this->visitors->approveDriverOut($c, $d)),
            'GENERATE_DRIVER_GATEPASS' => Action::post(Access::Auth, fn (Context $c, array $d) => $this->visitors->setDriverGatePass($c, $d)),
        ];
    }

    /** @param array<string,mixed> $q */
    private function getVisitor(Context $ctx, array $q, Request $req): array
    {
        $id = strtoupper(trim((string) ($q['id'] ?? '')));
        $v  = $this->visitors->findAnyUnit($id);
        if (!$v) {
            throw new ApiException('Visitor ID tidak ditemukan.');
        }
        if ($ctx->unit !== null && $ctx->unit !== $v['unit_id']) {
            throw new ApiException('Visitor ini terdaftar di unit lain (' . $v['unit_id'] . ').');
        }
        return ['visitor' => $this->visitors->presentPublic($v)];
    }

    /**
     * Daftar visitor yang sedang di dalam, untuk tab "Daftar" di app Check-Out.
     * Berisi nama orang, jadi MATI secara default: set PUBLIC_ACTIVE_LIST=true di .env bila gate Anda memang butuh.
     * @param array<string,mixed> $q
     */
    private function activeVisitors(Context $ctx, array $q, Request $req): array
    {
        if (!Env::bool('PUBLIC_ACTIVE_LIST')) {
            throw new ApiException('Daftar visitor aktif dinonaktifkan di server ini. Gunakan scan QR atau input ID.');
        }
        return ['visitors' => $this->visitors->listInside($ctx->requireUnit())];
    }

    /** @param array<string,mixed> $d */
    private function deleteRow(Context $ctx, array $d, Request $req): array
    {
        $id = (string) ($d['id'] ?? '');
        $unit = $ctx->requireUnit();
        switch ($d['sheet'] ?? 'Visitors') {
            case 'Visitors':
                $this->visitors->delete($ctx, $id);
                break;
            case 'Equipment':
                $this->equipment->delete($unit, $id);
                break;
            case 'AbsenKontraktor':
                $this->db->execute('DELETE FROM absen_kontraktor WHERE id = ? AND unit_id = ?', [$id, $unit]);
                break;
            default:
                throw new ApiException('Sheet tidak dikenal.');
        }
        return [];
    }
}
