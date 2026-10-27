<?php

declare(strict_types=1);

namespace Vsm\Core;

final class Action
{
    /** @param \Closure(Context,array<string,mixed>,Request):array<string,mixed> $handler */
    public function __construct(
        public readonly string $method,
        public readonly Access $access,
        public readonly \Closure $handler,
    ) {}

    public static function get(Access $a, \Closure $h): self
    {
        return new self('GET', $a, $h);
    }

    public static function post(Access $a, \Closure $h): self
    {
        return new self('POST', $a, $h);
    }
}
