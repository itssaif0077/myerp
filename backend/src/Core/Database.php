<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Enterprise PDO Database Connection Manager
 * Supports connection pooling/persistence, prepared statements, and atomic transactions.
 */
class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            if (empty(self::$config)) {
                $configFile = dirname(__DIR__, 2) . '/config/database.php';
                if (file_exists($configFile)) {
                    $dbConfig = require $configFile;
                    $defaultConn = $dbConfig['default'] ?? 'mysql';
                    self::$config = $dbConfig['connections'][$defaultConn] ?? [];
                }
            }

            $driver   = self::$config['driver'] ?? 'mysql';
            $host     = self::$config['host'] ?? '127.0.0.1';
            $port     = self::$config['port'] ?? 3306;
            $database = self::$config['database'] ?? 'erp_core';
            $charset  = self::$config['charset'] ?? 'utf8mb4';
            $username = self::$config['username'] ?? 'root';
            $password = self::$config['password'] ?? '';
            $options  = self::$config['options'] ?? [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                throw new RuntimeException("Database connection error: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }

    /**
     * Start an atomic database transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit the active transaction
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback the active transaction
     */
    public static function rollBack(): bool
    {
        if (self::getConnection()->inTransaction()) {
            return self::getConnection()->rollBack();
        }
        return false;
    }

    /**
     * Check if currently in an active transaction
     */
    public static function inTransaction(): bool
    {
        return self::getConnection()->inTransaction();
    }
}
