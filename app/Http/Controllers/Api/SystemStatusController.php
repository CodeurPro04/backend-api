<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SystemStatusController extends Controller
{
    public function index(Request $request)
    {
        $checks = [
            $this->checkApi(),
            $this->checkDatabase(),
            $this->checkCache(),
            $this->checkQueue(),
            $this->checkStorage(),
            $this->checkRealtime(),
        ];

        $statuses = array_column($checks, 'status');
        $overall = in_array('down', $statuses, true)
            ? 'down'
            : (in_array('degraded', $statuses, true) ? 'degraded' : 'operational');

        return response()->json([
            'success' => true,
            'data' => [
                'overall' => $overall,
                'checked_at' => now()->toIso8601String(),
                'services' => $checks,
            ],
        ]);
    }

    private function checkApi(): array
    {
        return ['key' => 'api', 'label' => 'API', 'status' => 'operational', 'detail' => null];
    }

    private function checkDatabase(): array
    {
        $start = microtime(true);
        try {
            DB::select('select 1');
            $ms = (int) round((microtime(true) - $start) * 1000);
            return ['key' => 'database', 'label' => 'Base de donnees', 'status' => 'operational', 'detail' => "{$ms} ms"];
        } catch (Throwable $e) {
            return ['key' => 'database', 'label' => 'Base de donnees', 'status' => 'down', 'detail' => 'Connexion impossible'];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = 'system_status_check_' . uniqid();
            Cache::put($key, '1', 5);
            $ok = Cache::get($key) === '1';
            Cache::forget($key);
            return ['key' => 'cache', 'label' => 'Cache', 'status' => $ok ? 'operational' : 'degraded', 'detail' => null];
        } catch (Throwable $e) {
            return ['key' => 'cache', 'label' => 'Cache', 'status' => 'down', 'detail' => 'Indisponible'];
        }
    }

    private function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed = DB::table('failed_jobs')->count();
            $status = $failed > 20 ? 'degraded' : 'operational';
            return [
                'key' => 'queue',
                'label' => "File d'attente",
                'status' => $status,
                'detail' => "{$pending} en attente" . ($failed > 0 ? ", {$failed} echouee(s)" : ''),
            ];
        } catch (Throwable $e) {
            return ['key' => 'queue', 'label' => "File d'attente", 'status' => 'down', 'detail' => 'Indisponible'];
        }
    }

    private function checkStorage(): array
    {
        try {
            $path = 'system-checks/health.txt';
            Storage::disk('public')->put($path, 'ok');
            $ok = Storage::disk('public')->exists($path);
            Storage::disk('public')->delete($path);
            return ['key' => 'storage', 'label' => 'Stockage fichiers', 'status' => $ok ? 'operational' : 'degraded', 'detail' => null];
        } catch (Throwable $e) {
            return ['key' => 'storage', 'label' => 'Stockage fichiers', 'status' => 'down', 'detail' => 'Ecriture impossible'];
        }
    }

    private function checkRealtime(): array
    {
        $default = config('broadcasting.default');
        $configured = $default
            && $default !== 'log'
            && $default !== 'null'
            && filled(config("broadcasting.connections.{$default}.key"));

        return [
            'key' => 'realtime',
            'label' => 'Temps reel',
            'status' => $configured ? 'operational' : 'degraded',
            'detail' => $configured ? null : 'Non configure',
        ];
    }
}
