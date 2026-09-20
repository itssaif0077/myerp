<?php
declare(strict_types=1);

namespace App\Domain\Entities;

/**
 * Pure Product Domain Entity
 * Strongly typed entity matching database fields and business constraints.
 */
class Product
{
    public function __construct(
        public ?int $id,
        public int $companyId,
        public string $name,
        public ?string $sku,
        public ?string $barcode,
        public string $category,
        public string $unit,
        public float $purchasePrice,
        public float $sellingPrice,
        public float $currentStock,
        public float $alertQuantity = 0.0,
        public bool $isQuickSell = false,
        public int $quickSellOrder = 0,
        public ?string $imageUrl = null,
        public string $status = 'active'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            companyId: (int)($data['company_id'] ?? 1),
            name: (string)($data['name'] ?? ''),
            sku: $data['sku'] ?? null,
            barcode: $data['barcode'] ?? null,
            category: (string)($data['category'] ?? 'General'),
            unit: (string)($data['unit'] ?? 'pcs'),
            purchasePrice: (float)($data['purchase_price'] ?? 0.0),
            sellingPrice: (float)($data['selling_price'] ?? 0.0),
            currentStock: (float)($data['current_stock'] ?? 0.0),
            alertQuantity: (float)($data['alert_quantity'] ?? 0.0),
            isQuickSell: (bool)($data['is_quick_sell'] ?? false),
            quickSellOrder: (int)($data['quick_sell_order'] ?? 0),
            imageUrl: $data['image_url'] ?? null,
            status: (string)($data['status'] ?? 'active')
        );
    }
}
