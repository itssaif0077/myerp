<?php
declare(strict_types=1);

namespace App\Domain\Repositories;

/**
 * Product Repository Interface (Contract)
 * Decouples business logic from SQL/persistence implementation (mirrors C# IProductRepository).
 */
interface IProductRepository
{
    public function findById(int $id): ?array;
    public function findByCompany(int $companyId, int $id): ?array;
    public function getQuickSellProducts(int $companyId): array;
    public function decrementStockAtomic(int $productId, float $quantity): bool;
    public function incrementStockAtomic(int $productId, float $quantity): bool;
}
