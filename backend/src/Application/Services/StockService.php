<?php
declare(strict_types=1);

namespace App\Application\Services;

use App\Core\Database;
use App\Domain\Repositories\IProductRepository;
use PDO;
use RuntimeException;

/**
 * Stock Management Service (Inventory Engine)
 * Manages atomic inventory adjustments, purchase additions, and sales deductions.
 */
class StockService
{
    private PDO $db;
    private IProductRepository $productRepo;

    public function __construct(IProductRepository $productRepo)
    {
        $this->db = Database::getConnection();
        $this->productRepo = $productRepo;
    }

    /**
     * Deduct stock for a sale and record in stock_transactions audit log
     */
    public function deductStock(
        int $companyId,
        int $productId,
        float $quantity,
        float $unitPrice,
        string $referenceType,
        int $referenceId,
        ?int $userId = null
    ): void {
        // 1. Deduct atomically
        $success = $this->productRepo->decrementStockAtomic($productId, $quantity);
        if (!$success) {
            throw new RuntimeException("Insufficient stock available for Product ID #{$productId}.");
        }

        // 2. Record stock transaction audit
        $stmt = $this->db->prepare("
            INSERT INTO stock_transactions 
            (company_id, product_id, transaction_type, quantity, unit_price, reference_type, reference_id, created_by)
            VALUES 
            (:company_id, :product_id, 'sale', :quantity, :unit_price, :ref_type, :ref_id, :user_id)
        ");
        $stmt->execute([
            'company_id' => $companyId,
            'product_id' => $productId,
            'quantity'   => $quantity,
            'unit_price' => $unitPrice,
            'ref_type'   => $referenceType,
            'ref_id'     => $referenceId,
            'user_id'    => $userId,
        ]);
    }
}
