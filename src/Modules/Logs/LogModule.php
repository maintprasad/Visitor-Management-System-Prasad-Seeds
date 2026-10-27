<?php

declare(strict_types=1);

namespace Vsm\Modules\Logs;

use Vsm\Core\Access;
use Vsm\Core\Action;
use Vsm\Core\Context;
use Vsm\Core\Module;
use Vsm\Core\Request;

final class LogModule implements Module
{
    public function __construct(private readonly LogRepository $logs) {}

    public function actions(): array
    {
        return [
            'ADD_LOG'  => Action::post(Access::Public, $this->add(...)),
            'SCAN_QR'  => Action::post(Access::Auth, $this->scan(...)),
        ];
    }

    /** @param array<string,mixed> $d */
    private function add(Context $ctx, array $d, Request $req): array
    {
        // App publik (Check-In/Check-Out) tidak boleh menulis log bebas;
        // server sendiri sudah mencatat SELF_REGISTER & CHECK_OUT.
        if (!$ctx->isAuthenticated()) {
            return [];
        }
        $this->logs->write(
            $ctx->requireUnit(),
            (string) ($d['action'] ?? 'LOG'),
            isset($d['entity']) ? (string) $d['entity'] : null,
            isset($d['refId']) ? (string) $d['refId'] : null,
            (string) ($d['detail'] ?? ''),
            $ctx->userName(),
            $d['ts'] ?? null,
        );
        return [];
    }

    /** @param array<string,mixed> $d */
    private function scan(Context $ctx, array $d, Request $req): array
    {
        $this->logs->write($ctx->requireUnit(), 'SCAN_QR', 'QR', (string) ($d['refId'] ?? ''), (string) ($d['action'] ?? ''), $ctx->userName());
        return [];
    }
}
