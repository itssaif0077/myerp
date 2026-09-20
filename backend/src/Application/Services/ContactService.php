<?php
declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\ContactDTO;
use App\Application\Exceptions\DuplicateContactException;
use App\Domain\Repositories\IContactRepository;
use RuntimeException;

/**
 * Contact Domain Service
 * Encapsulates multi-firm business logic for customers, suppliers, karigars, and staff.
 */
class ContactService
{
    private IContactRepository $contactRepo;

    public function __construct(IContactRepository $contactRepo)
    {
        $this->contactRepo = $contactRepo;
    }

    public function listContacts(int $companyId, array $filters, int $page = 1, int $limit = 100): array
    {
        return $this->contactRepo->filterContacts($companyId, $filters, $page, $limit);
    }

    public function getContact(int $companyId, int $id): ?array
    {
        $contact = $this->contactRepo->findByCompany($companyId, $id);
        if (!$contact) {
            throw new RuntimeException("Contact #{$id} not found in current company.");
        }
        return $contact;
    }

    public function createContact(ContactDTO $dto, bool $forceCreate = false): array
    {
        // Duplicate check on phone within the same firm
        if (!empty($dto->phone)) {
            $existing = $this->contactRepo->findByPhone($dto->companyId, $dto->phone);
            if ($existing && !$forceCreate) {
                throw new DuplicateContactException(
                    "A contact with phone '{$dto->phone}' already exists ({$existing['name']}, ID #{$existing['id']}). Pass 'force_create': true to allow a shared phone number.",
                    $existing
                );
            }
        }

        $data = $dto->toArray();
        $id = $this->contactRepo->createContact($data);
        return $this->getContact($dto->companyId, $id);
    }

    public function updateContact(int $companyId, int $id, array $updateData): array
    {
        // Verify contact belongs to company
        $existing = $this->getContact($companyId, $id);

        // Disallow changing company_id, id, created_at
        unset($updateData['id'], $updateData['company_id'], $updateData['created_at']);

        // If no fields provided to update, return cleanly without running DB query
        if (empty($updateData)) {
            return $existing;
        }

        $this->contactRepo->updateContact($id, $updateData);
        return $this->getContact($companyId, $id);
    }

    public function deleteContact(int $companyId, int $id): bool
    {
        // Verify contact belongs to company
        $this->getContact($companyId, $id);
        return $this->contactRepo->deleteContact($id);
    }
}
