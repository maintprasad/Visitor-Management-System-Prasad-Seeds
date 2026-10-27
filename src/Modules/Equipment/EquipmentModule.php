<?php

declare(strict_types=1);

namespace Vsm\Modules\Equipment;

use Vsm\Core\Access;
use Vsm\Core\Action;
use Vsm\Core\ApiException;
use Vsm\Core\Clock;
use Vsm\Core\Context;
use Vsm\Core\Module;
use Vsm\Core\Request;

final class EquipmentModule implements Module
{
    public function __construct(private readonly EquipmentRepository $equipment) {}

    public function actions(): array
    {
        return [
            'ADD_EQUIPMENT' => Action::post(Access::Auth, $this->add(...)),
            'EQUIPMENT_OUT' => Action::post(Access::Auth, $this->out(...)),
        ];
    }

    /** @param array<string,mixed> $d */
    private function add(Context $ctx, array $d, Request $req): array
    {
        if (trim((string) ($d['name'] ?? '')) === '') {
            throw new ApiException('Nama equipment wajib diisi.');
        }
        $d['user'] = $d['user'] ?? $ctx->userName();
        // Idempotent: antrian retry klien bisa mengirim ulang setelah server sempat menyimpan.
        $existing = isset($d['id']) ? $this->equipment->find($ctx->requireUnit(), (string) $d['id']) : null;
        return ['id' => $existing['id'] ?? $this->equipment->insert($ctx->requireUnit(), $d)];
    }

    /** @param array<string,mixed> $d */
    private function out(Context $ctx, array $d, Request $req): array
    {
        $id = (string) ($d['id'] ?? '');
        if (!$this->equipment->find($ctx->requireUnit(), $id)) {
            throw new ApiException('Equipment tidak ditemukan di unit ini.');
        }
        $this->equipment->setStatus($ctx->requireUnit(), $id, 'Outside', Clock::normalize($d['dateOut'] ?? null) ?? Clock::now());
        return ['id' => $id];
    }
}
