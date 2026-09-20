<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use Throwable;

/**
 * Health & Diagnostics Controller
 * Provides real-time server diagnostics, latency, memory usage, and DB connectivity.
 */
class HealthController
{
    public function check(Request $request): void
    {
        $dbStatus = 'disconnected';
        $dbLatencyMs = null;

        try {
            $start = microtime(true);
            $pdo = Database::getConnection();
            $pdo->query('SELECT 1');
            $dbLatencyMs = round((microtime(true) - $start) * 1000, 2);
            $dbStatus = 'connected';
        } catch (Throwable $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }

        Response::ok([
            'status'      => 'healthy',
            'environment' => Env::get('APP_ENV', 'local'),
            'php_version' => PHP_VERSION,
            'memory_used' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'server_time' => date('Y-m-d H:i:s'),
            'database'    => [
                'status'     => $dbStatus,
                'latency_ms' => $dbLatencyMs,
            ]
        ], 'API is running normally');
    }
}
