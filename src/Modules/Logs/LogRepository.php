<?php

declare(strict_types=1);

namespace Vsm\Modules\Logs;

use Vsm\Core\Clock;
use Vsm\Database\Connection;

final class LogRepository
{
    public function __construct(private readonly Connection $db) {}

    public function write(string $unit, string $action, ?string $entity, ?string $refId, ?string $detail, ?string $user, ?string $ts = null): void
    {
        $this->db->execute(
            'INSERT INTO logs (unit_id, ts, action, entity, ref_id, detail, user_name) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$unit, Clock::normalize($ts) ?? Clock::now(), mb_substr($action, 0, 40), $entity, $refId, $detail, $user]
        );
    }
}
