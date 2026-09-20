<?php
declare(strict_types=1);

namespace App\Application\DTOs;

use InvalidArgumentException;

/**
 * Quick Sale Data Transfer Object (DTO)
 * Validates and strictly types incoming POS checkout payloads.
 */
class QuickSaleDTO
{
    /**
     * @param int $companyId
     * @param int|null $customerId
     * @param array $items Array of ['product_id' => int, 'quantity' => float, 'unit_price' => float]
     * @param string $paymentMethod
     * @param float $paidAmount
     * @param int|null $userId
     */
    public function __construct(
        public int $companyId,
        public ?int $customerId,
        public array $items,
        public string $paymentMethod,
        public float $paidAmount,
        public ?int $userId = null
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->companyId <= 0) {
            throw new InvalidArgumentException("Invalid company_id specified.");
        }

        if (empty($this->items)) {
            throw new InvalidArgumentException("At least one item is required for checkout.");
        }

        foreach ($this->items as $index => $item) {
            if (!isset($item['product_id']) || (int)$item['product_id'] <= 0) {
                throw new InvalidArgumentException("Item at index {$index} must have a valid product_id.");
            }
            if (!isset($item['quantity']) || (float)$item['quantity'] <= 0) {
                throw new InvalidArgumentException("Item at index {$index} must have a positive quantity.");
            }
            if (!isset($item['unit_price']) || (float)$item['unit_price'] < 0) {
                throw new InvalidArgumentException("Item at index {$index} must have a valid unit_price.");
            }
        }

        if ($this->paidAmount < 0) {
            throw new InvalidArgumentException("Paid amount cannot be negative.");
        }
    }

    public static function fromRequest(array $body, int $companyId, ?int $userId = null): self
    {
        return new self(
            companyId: $companyId,
            customerId: isset($body['customer_id']) ? (int)$body['customer_id'] : null,
            items: (array)($body['items'] ?? []),
            paymentMethod: (string)($body['payment_method'] ?? 'cash'),
            paidAmount: (float)($body['paid_amount'] ?? 0.0),
            userId: $userId
        );
    }
}
