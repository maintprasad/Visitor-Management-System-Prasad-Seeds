<?php

declare(strict_types=1);

namespace Vsm\Modules\Sync;

use Vsm\Core\Access;
use Vsm\Core\Action;
use Vsm\Core\ApiException;
use Vsm\Core\Context;
use Vsm\Core\Module;
use Vsm\Core\Request;
use Vsm\Database\Connection;

/** Pengganti GET_SHEET_DATA / GET_SHEET_VERSION Apps Script — selalu terfilter per unit. */
final class SyncModule implements Module
{
    public function __construct(private readonly Connection $db) {}

    public function actions(): array
    {
        return [
            'GET_SHEET_DATA'    => Action::get(Access::Auth, $this->data(...)),
            'GET_SHEET_VERSION' => Action::get(Access::Auth, $this->version(...)),
        ];
    }

    /** @param array<string,mixed> $q */
    private function data(Context $ctx, array $q, Request $req): array
    {
        $def  = SheetDefinitions::get((string) ($q['sheet'] ?? '')) ?? throw new ApiException('Sheet tidak dikenal.');
        $cols = implode(', ', array_map(fn ($c) => $c[1], $def['columns']));
        $limit = $def['table'] === 'logs' ? min(2000, max(1, (int) ($q['limit'] ?? 500))) : 20000;

        $rows = $this->db->select(
            "SELECT {$cols} FROM {$def['table']} WHERE {$def['unit']} = ? ORDER BY {$def['order']} LIMIT {$limit}",
            [$ctx->requireUnit()]
        );

        $out = [];
        foreach ($rows as $r) {
            $line = [];
            foreach ($def['columns'] as [, $col, $type]) {
                $v = $r[$col] ?? null;
                $line[] = match ($type) {
                    'b' => $v ? 'TRUE' : 'FALSE',
                    'n' => $v === null || $v === '' ? '' : $v + 0,
                    default => $v === null ? '' : (string) $v,
                };
            }
            $out[] = $line;
        }
        return ['sheet' => $q['sheet'], 'unit' => $ctx->unit, 'headers' => array_map(fn ($c) => $c[0], $def['columns']), 'rows' => $out];
    }

    /** @param array<string,mixed> $q */
    private function version(Context $ctx, array $q, Request $req): array
    {
        $def = SheetDefinitions::get((string) ($q['sheet'] ?? '')) ?? throw new ApiException('Sheet tidak dikenal.');
        $r = $this->db->select(
            "SELECT COUNT(*) AS c, MAX({$def['version']}) AS m FROM {$def['table']} WHERE {$def['unit']} = ?",
            [$ctx->requireUnit()]
        )[0];
        return ['fingerprint' => md5($r['c'] . '|' . $r['m'])];
    }
}
