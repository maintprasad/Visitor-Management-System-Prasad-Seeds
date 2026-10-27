<?php

declare(strict_types=1);

namespace Vsm\Core;

/** Error yang aman ditampilkan ke klien (dikirim sebagai {ok:false,error}). */
class ApiException extends \RuntimeException
{
    /** @param array<string,mixed> $extra */
    public function __construct(string $message, public readonly int $status = 200, public readonly array $extra = [])
    {
        parent::__construct($message);
    }
}
