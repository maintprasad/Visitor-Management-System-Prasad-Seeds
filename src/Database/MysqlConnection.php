<?php

declare(strict_types=1);

namespace Vsm\Database;

use PDO;

final class MysqlConnection implements Connection
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $database,
        private readonly string $username,
        private readonly string $password,
    ) {}

    public function driver(): string
    {
        return 'mysql';
    }

    private function pdo(): PDO
    {
        return $this->pdo ??= new PDO(
            "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4",
            $this->username,
            $this->password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public function select(string $sql, array $params = []): array
    {
        $st = $this->pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function execute(string $sql, array $params = []): int
    {
        $st = $this->pdo()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public function batch(array $statements): void
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            foreach ($statements as [$sql, $params]) {
                $pdo->prepare($sql)->execute($params);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
