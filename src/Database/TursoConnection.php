<?php

declare(strict_types=1);

namespace Vsm\Database;

/**
 * Turso (libSQL) lewat HTTP API (/v2/pipeline) — tanpa ekstensi khusus,
 * hanya butuh ext-curl. Cocok untuk PHP serverless di Vercel.
 */
final class TursoConnection implements Connection
{
    private readonly string $endpoint;

    public function __construct(string $url, private readonly string $authToken)
    {
        // libsql://nama-db.turso.io  ->  https://nama-db.turso.io
        $url = preg_replace('#^libsql://#', 'https://', trim($url)) ?? $url;
        $this->endpoint = rtrim($url, '/') . '/v2/pipeline';
    }

    public function driver(): string
    {
        return 'turso';
    }

    public function select(string $sql, array $params = []): array
    {
        $res  = $this->pipeline([$this->stmt($sql, $params)])[0];
        $cols = array_map(static fn ($c) => $c['name'], $res['cols'] ?? []);
        $out  = [];
        foreach ($res['rows'] ?? [] as $row) {
            $r = [];
            foreach ($row as $i => $cell) {
                $r[$cols[$i]] = $this->decode($cell);
            }
            $out[] = $r;
        }
        return $out;
    }

    public function execute(string $sql, array $params = []): int
    {
        $res = $this->pipeline([$this->stmt($sql, $params)])[0];
        return (int) ($res['affected_row_count'] ?? 0);
    }

    public function batch(array $statements): void
    {
        // Satu pipeline = satu stream/koneksi, jadi BEGIN..COMMIT atomik.
        // Bila ada yang gagal pipeline berhenti dan transaksi di-rollback saat stream ditutup.
        $reqs = [$this->stmt('BEGIN', [])];
        foreach ($statements as [$sql, $params]) {
            $reqs[] = $this->stmt($sql, $params);
        }
        $reqs[] = $this->stmt('COMMIT', []);
        $this->pipeline($reqs);
    }

    /** @return array<string,mixed> */
    private function stmt(string $sql, array $params): array
    {
        return [
            'type' => 'execute',
            'stmt' => ['sql' => $sql, 'args' => array_map(fn ($p) => $this->encode($p), array_values($params))],
        ];
    }

    /** @return array<string,mixed> */
    private function encode(mixed $v): array
    {
        return match (true) {
            $v === null => ['type' => 'null'],
            is_bool($v) => ['type' => 'integer', 'value' => $v ? '1' : '0'],
            is_int($v) => ['type' => 'integer', 'value' => (string) $v],
            is_float($v) => ['type' => 'float', 'value' => $v],
            default => ['type' => 'text', 'value' => (string) $v],
        };
    }

    private function decode(array $cell): mixed
    {
        return match ($cell['type'] ?? 'null') {
            'null' => null,
            'integer' => (int) $cell['value'],
            'float' => (float) $cell['value'],
            default => $cell['value'],
        };
    }

    /**
     * @param list<array<string,mixed>> $requests
     * @return list<array<string,mixed>> `result` tiap statement (tanpa BEGIN/COMMIT)
     */
    private function pipeline(array $requests): array
    {
        $isBatch    = count($requests) > 1;
        $requests[] = ['type' => 'close'];

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->authToken,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(['requests' => $requests], JSON_UNESCAPED_UNICODE),
        ]);
        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($raw === false) {
            // CurlHandle ditutup otomatis (curl_close() deprecated di PHP 8.5).
            throw new \RuntimeException('Turso tidak terjangkau: ' . curl_error($ch));
        }

        $json = json_decode((string) $raw, true);
        if ($code >= 400 || !is_array($json)) {
            throw new \RuntimeException("Turso HTTP {$code}: " . substr((string) $raw, 0, 300));
        }

        $results = [];
        foreach ($json['results'] as $r) {
            if (($r['type'] ?? '') === 'error') {
                throw new \RuntimeException('Turso: ' . ($r['error']['message'] ?? 'error tidak dikenal'));
            }
            if (($r['response']['type'] ?? '') === 'execute') {
                $results[] = $r['response']['result'];
            }
        }
        return $isBatch ? array_slice($results, 1, -1) : $results;
    }
}
