<?php
declare(strict_types=1);

namespace App\Domain\Repositories;

/**
 * Contact Repository Interface (Contract)
 * Decouples business logic from SQL/persistence implementation.
 */
interface IContactRepository
{
    public function findById(int $id): ?array;
    public function findByCompany(int $companyId, int $id): ?array;
    public function findByPhone(int $companyId, string $phone): ?array;
    public function filterContacts(int $companyId, array $filters = [], int $page = 1, int $limit = 50): array;
    public function countFiltered(int $companyId, array $filters = []): int;
    public function createContact(array $data): int;
    public function updateContact(int $id, array $data): bool;
    public function deleteContact(int $id): bool;
    public function updateBalance(int $contactId, float $amountChange): bool;
}
