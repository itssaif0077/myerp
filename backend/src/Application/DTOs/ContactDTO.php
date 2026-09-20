<?php
declare(strict_types=1);

namespace App\Application\DTOs;

use InvalidArgumentException;

/**
 * Contact Data Transfer Object (DTO)
 * Validates and strictly types incoming contact creation/update payloads.
 */
class ContactDTO
{
    public function __construct(
        public int $companyId,
        public string $type,
        public string $name,
        public string $staffRole = 'none',
        public ?string $companyName = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $gstVatNumber = null,
        public ?string $address = null,
        public float $openingBalance = 0.0,
        public string $balanceType = 'debit',
        public string $status = 'active'
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->companyId <= 0) {
            throw new InvalidArgumentException("Valid company_id is required.");
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException("Contact name is required.");
        }

        if (trim($this->type) === '') {
            throw new InvalidArgumentException("Contact type is required (e.g. customer, supplier, karigar, staff).");
        }

        if (!in_array($this->balanceType, ['debit', 'credit'], true)) {
            throw new InvalidArgumentException("Balance type must be 'debit' or 'credit'.");
        }
    }

    public static function fromRequest(array $data, int $companyId): self
    {
        return new self(
            companyId: $companyId,
            type: strtolower(trim((string)($data['type'] ?? 'customer'))),
            name: trim((string)($data['name'] ?? '')),
            staffRole: strtolower(trim((string)($data['staff_role'] ?? 'none'))),
            companyName: !empty($data['company_name']) ? trim((string)$data['company_name']) : null,
            email: !empty($data['email']) ? trim((string)$data['email']) : null,
            phone: !empty($data['phone']) ? trim((string)$data['phone']) : null,
            gstVatNumber: !empty($data['gst_vat_number']) ? trim((string)$data['gst_vat_number']) : null,
            address: !empty($data['address']) ? trim((string)$data['address']) : null,
            openingBalance: (float)($data['opening_balance'] ?? 0.0),
            balanceType: strtolower((string)($data['balance_type'] ?? 'debit')),
            status: strtolower((string)($data['status'] ?? 'active'))
        );
    }

    public function toArray(): array
    {
        return [
            'company_id'      => $this->companyId,
            'type'            => $this->type,
            'name'            => $this->name,
            'staff_role'      => $this->staffRole,
            'company_name'    => $this->companyName,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'gst_vat_number'  => $this->gstVatNumber,
            'address'         => $this->address,
            'opening_balance' => $this->openingBalance,
            'balance_type'    => $this->balanceType,
            'current_balance' => $this->openingBalance, // Initialized to opening balance
            'status'          => $this->status,
        ];
    }
}
