<?php

declare(strict_types=1);

namespace Vsm\Database;

interface Connection
{
    /** 'mysql' | 'turso' */
    public function driver(): string;

    /** @param list<scalar|null> $params @return list<array<string,mixed>> */
    public function select(string $sql, array $params = []): array;

    /** @param list<scalar|null> $params @return int baris terpengaruh */
    public function execute(string $sql, array $params = []): int;

    /** Jalankan beberapa pernyataan secara atomik. @param list<array{0:string,1:list<scalar|null>}> $statements */
    public function batch(array $statements): void;
}
