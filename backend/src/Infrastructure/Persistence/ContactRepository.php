<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\IContactRepository;
use PDO;

/**
 * Concrete Contact Repository Implementation
 * Handles multi-firm customer, supplier, karigar, and staff database access.
 */
class ContactRepository extends BaseRepository implements IContactRepository
{
    protected string $table = 'contacts';

    public function findByPhone(int $companyId, string $phone): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE company_id = :company_id AND phone = :phone AND status != 'deleted' 
            LIMIT 1
        ");
        $stmt->execute(['company_id' => $companyId, 'phone' => $phone]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function filterContacts(int $companyId, array $filters = [], int $page = 1, int $limit = 50): array
    {
        $where = ['company_id = :company_id'];
        $params = ['company_id' => $companyId];

        // Filter by contact type (customer, supplier, karigar, staff, etc.)
        if (!empty($filters['type'])) {
            $where[] = 'type = :type';
            $params['type'] = $filters['type'];
        }

        // Filter by staff_role
        if (!empty($filters['staff_role'])) {
            $where[] = 'staff_role = :staff_role';
            $params['staff_role'] = $filters['staff_role'];
        }

        // Filter by status (active/inactive)
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        // Search by name, phone, or company_name
        if (!empty($filters['search'])) {
            $where[] = '(name LIKE :search OR phone LIKE :search OR company_name LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // Date range filter
        if (!empty($filters['from_date'])) {
            $where[] = 'DATE(created_at) >= :from_date';
            $params['from_date'] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where[] = 'DATE(created_at) <= :to_date';
            $params['to_date'] = $filters['to_date'];
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT * FROM {$this->table}
            WHERE {$whereClause}
            ORDER BY id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue(":{$key}", $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countFiltered(int $companyId, array $filters = []): int
    {
        $where = ['company_id = :company_id'];
        $params = ['company_id' => $companyId];

        if (!empty($filters['type'])) {
            $where[] = 'type = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['staff_role'])) {
            $where[] = 'staff_role = :staff_role';
            $params['staff_role'] = $filters['staff_role'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(name LIKE :search OR phone LIKE :search OR company_name LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['from_date'])) {
            $where[] = 'DATE(created_at) >= :from_date';
            $params['from_date'] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where[] = 'DATE(created_at) <= :to_date';
            $params['to_date'] = $filters['to_date'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function createContact(array $data): int
    {
        return $this->create($data);
    }

    public function updateContact(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteContact(int $id): bool
    {
        // Soft delete / archive by marking inactive
        return $this->update($id, ['status' => 'inactive']);
    }

    public function updateBalance(int $contactId, float $amountChange): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET current_balance = current_balance + :change, updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $contactId, 'change' => $amountChange]);
    }
}
