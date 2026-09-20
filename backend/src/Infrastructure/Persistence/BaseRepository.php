<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Core\Database;
use PDO;

/**
 * Universal Base Repository (Data Access Layer)
 * Provides high-speed generic CRUD operations for all 100+ entities without code duplication.
 */
abstract class BaseRepository
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByCompany(int $companyId, int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE {$this->primaryKey} = :id AND company_id = :company_id 
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'company_id' => $companyId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findAllByCompany(int $companyId, array $orderBy = ['id' => 'DESC'], int $limit = 100, int $offset = 0): array
    {
        $orderClauses = [];
        foreach ($orderBy as $col => $dir) {
            $safeDir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
            $safeCol = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
            $orderClauses[] = "{$safeCol} {$safeDir}";
        }
        $orderSql = implode(', ', $orderClauses);

        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE company_id = :company_id 
            ORDER BY {$orderSql} 
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int
    {
        $columns = implode(', ', array_map(fn($k) => "`" . str_replace('`', '', $k) . "`", array_keys($data)));
        $placeholders = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $setClauses = [];
        foreach (array_keys($data) as $column) {
            $setClauses[] = "`{$column}` = :{$column}";
        }
        $setSql = implode(', ', $setClauses);

        $data['id'] = $id;
        $sql = "UPDATE {$this->table} SET {$setSql}, updated_at = NOW() WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function countByCompany(int $companyId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE company_id = :company_id");
        $stmt->execute(['company_id' => $companyId]);
        return (int)$stmt->fetchColumn();
    }
}
