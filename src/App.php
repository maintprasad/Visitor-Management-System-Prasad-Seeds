<?php

declare(strict_types=1);

namespace Vsm;

use Vsm\Core\Access;
use Vsm\Core\Action;
use Vsm\Core\ApiException;
use Vsm\Core\Context;
use Vsm\Core\Env;
use Vsm\Core\Module;
use Vsm\Core\Request;
use Vsm\Core\Response;
use Vsm\Core\Token;
use Vsm\Database\Connection;
use Vsm\Database\Database;
use Vsm\Modules\Absen\AbsenModule;
use Vsm\Modules\Auth\AuthModule;
use Vsm\Modules\Equipment\EquipmentModule;
use Vsm\Modules\Equipment\EquipmentRepository;
use Vsm\Modules\Logs\LogModule;
use Vsm\Modules\Logs\LogRepository;
use Vsm\Modules\Sync\SyncModule;
use Vsm\Modules\Units\UnitRepository;
use Vsm\Modules\Visitors\VisitorModule;
use Vsm\Modules\Visitors\VisitorService;

/**
 * Front controller bersama (lokal Apache/XAMPP, php -S, dan Vercel).
 *   /api   -> dispatcher aksi (protokol {action,data} -> {ok,...}, kompatibel dgn versi Apps Script)
 *   /      -> panel kontrol security
 */
final class App
{
    private Connection $db;
    private UnitRepository $units;
    /** @var array<string,Action> */
    private array $actions = [];

    public function __construct()
    {
        $this->db    = Database::connection();
        $this->units = new UnitRepository($this->db);

        $equipment = new EquipmentRepository($this->db);
        $logs      = new LogRepository($this->db);
        $visitors  = new VisitorService($this->db, $equipment, $logs, $this->units);

        /** @var list<Module> $modules */
        $modules = [
            new AuthModule($this->db, $this->units),
            new VisitorModule($visitors, $equipment, $this->db),
            new EquipmentModule($equipment),
            new AbsenModule($this->db),
            new LogModule($logs),
            new SyncModule($this->db),
        ];
        foreach ($modules as $m) {
            $this->actions += $m->actions();
        }
    }

    public function handle(Request $req): never
    {
        if ($req->method === 'OPTIONS') {
            Response::cors();
            http_response_code(204);
            exit;
        }
        if ($req->path === '/api' || str_starts_with($req->path, '/api/')) {
            $this->api($req);
        }
        $this->panel();
    }

    private function api(Request $req): never
    {
        try {
            $action = strtoupper((string) ($req->query['action'] ?? $req->body['action'] ?? $req->body['type'] ?? ''));
            $def    = $this->actions[$action] ?? throw new ApiException($action === '' ? 'Parameter action wajib.' : "Action tidak dikenal: {$action}", 404);

            if ($def->method !== $req->method) {
                throw new ApiException("Action {$action} harus memakai {$def->method}.", 405);
            }

            $data = $req->method === 'GET' ? $req->query : (is_array($req->body['data'] ?? null) ? $req->body['data'] : []);
            $ctx  = $this->context($req, $data, $def->access);

            $result = ($def->handler)($ctx, $data, $req);
            Response::json(['ok' => true] + $result);
        } catch (ApiException $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()] + $e->extra, $e->status);
        } catch (\Throwable $e) {
            error_log('[VSM] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $msg = Env::bool('APP_DEBUG') ? $e->getMessage() : 'Terjadi kesalahan server.';
            Response::json(['ok' => false, 'error' => $msg], 500);
        }
    }

    /**
     * Unit TIDAK PERNAH dipercaya dari klien untuk petugas security — dipaksa dari token.
     * Admin boleh berpindah unit (X-Unit / ?unit=). App publik wajib menyebut unit sendiri.
     *
     * @param array<string,mixed> $data
     */
    private function context(Request $req, array $data, Access $access): Context
    {
        $claims = Token::verify($req->bearer());

        if ($access !== Access::Public && !$claims) {
            throw new ApiException('Sesi berakhir. Silakan login kembali.', 401);
        }
        if ($access === Access::Admin && ($claims['role'] ?? '') !== 'admin') {
            throw new ApiException('Hanya admin yang boleh melakukan ini.', 403);
        }

        $requested = $req->header('X-Unit') ?? ($data['unit'] ?? $req->query['unit'] ?? null);

        if ($claims) {
            $unit = $claims['unit'] ?? null;
            if (($claims['role'] ?? '') === 'admin' && $requested !== null) {
                $unit = $this->units->resolve($requested) ?? throw new ApiException('Unit tidak dikenal.');
            }
            return new Context($claims, $unit);
        }

        $unit = null;
        if ($requested !== null && $requested !== '') {
            $unit = $this->units->resolve($requested) ?? throw new ApiException('Unit tidak dikenal.');
        }
        return new Context(null, $unit);
    }

    private function panel(): never
    {
        $config = [
            'base'    => Request::basePath(),
            'apiBase' => Request::basePath() . '/api',
            'units'   => $this->units->active(),
        ];
        ob_start();
        require BASE_PATH . '/views/layout.php';
        Response::html((string) ob_get_clean());
    }
}
