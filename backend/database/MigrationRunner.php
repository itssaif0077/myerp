<?php
declare(strict_types=1);

namespace Database;

use App\Core\Database;
use PDO;
use Throwable;

/**
 * Enterprise Database Migration Runner
 * Tracks executed migrations in a `migrations` table and runs up()/down() methods.
 */
class MigrationRunner
{
    private PDO $pdo;
    private string $migrationsPath;

    public function __construct(string $migrationsPath)
    {
        $this->pdo = Database::getConnection();
        $this->migrationsPath = $migrationsPath;
        $this->ensureMigrationsTable();
    }

    private function ensureMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT UNSIGNED NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $this->pdo->exec($sql);
    }

    private function getExecutedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private function getNextBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) FROM migrations");
        $max = $stmt->fetchColumn();
        return $max ? ((int)$max + 1) : 1;
    }

    public function migrate(): void
    {
        echo "\n=== Running Database Migrations ===\n";

        $files = glob($this->migrationsPath . '/*.php');
        sort($files);

        $executed = $this->getExecutedMigrations();
        $batch = $this->getNextBatchNumber();
        $count = 0;

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');

            if (in_array($migrationName, $executed, true)) {
                continue;
            }

            echo "Migrating: {$migrationName} ... ";

            $migration = require_once $file;

            if (!is_object($migration) || !method_exists($migration, 'up')) {
                echo "FAILED (Invalid migration class)\n";
                continue;
            }

            try {
                $migration->up($this->pdo);

                $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)");
                $stmt->execute(['migration' => $migrationName, 'batch' => $batch]);

                echo "DONE\n";
                $count++;
            } catch (Throwable $e) {
                echo "FAILED!\n";
                echo "Error: " . $e->getMessage() . "\n";
                exit(1);
            }
        }

        if ($count === 0) {
            echo "Nothing to migrate. Database is up to date.\n";
        } else {
            echo "\nSuccessfully migrated {$count} files.\n";
        }
    }
}
