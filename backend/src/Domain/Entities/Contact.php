<?php
declare(strict_types=1);

namespace App\Domain\Entities;

/**
 * Pure Contact Domain Entity
 * Represents Customers, Suppliers, Karigars, Staff, and Brokers.
 */
class Contact
{
    public function __construct(
        public ?int $id,
        public int $companyId,
        public string $type, // 'customer', 'supplier', 'karigar', 'staff', 'broker', etc.
        public string $staffRole = 'none',
        public string $name = '',
        public ?string $companyName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $gstVatNumber = null,
        public ?string $address = null,
        public float $openingBalance = 0.0,
        public string $balanceType = 'debit',
        public float $currentBalance = 0.0,
        public string $status = 'active'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            companyId: (int)($data['company_id'] ?? 1),
            type: (string)($data['type'] ?? 'customer'),
            staffRole: (string)($data['staff_role'] ?? 'none'),
            name: (string)($data['name'] ?? ''),
            companyName: $data['company_name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            gstVatNumber: $data['gst_vat_number'] ?? null,
            address: $data['address'] ?? null,
            openingBalance: (float)($data['opening_balance'] ?? 0.0),
            balanceType: (string)($data['balance_type'] ?? 'debit'),
            currentBalance: (float)($data['current_balance'] ?? 0.0),
            status: (string)($data['status'] ?? 'active')
        );
    }
}
