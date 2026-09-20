<?php
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\IProductRepository;
use PDO;

/**
 * Concrete Product Repository Implementation
 * Inherits generic CRUD from BaseRepository and provides high-concurrency atomic stock operations.
 */
class ProductRepository extends BaseRepository implements IProductRepository
{
    protected string $table = 'products';

    /**
     * Fetch products marked for Quick-Sell POS grid (e.g. Vadapav ₹20)
     */
    public function getQuickSellProducts(int $companyId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, sku, barcode, category, selling_price, current_stock, image_url
            FROM {$this->table}
            WHERE company_id = :company_id AND is_quick_sell = 1 AND status = 'active'
            ORDER BY quick_sell_order ASC, name ASC
        ");
        $stmt->execute(['company_id' => $companyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * High-concurrency atomic stock deduction.
     * Prevents race conditions / overselling without full table locks.
     */
    public function decrementStockAtomic(int $productId, float $quantity): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET current_stock = current_stock - :qty_dec, updated_at = NOW()
            WHERE id = :id AND current_stock >= :qty_check
        ");
        $stmt->execute([
            'id'        => $productId,
            'qty_dec'   => $quantity,
            'qty_check' => $quantity
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Atomic stock increment (e.g., for purchases or returns)
     */
    public function incrementStockAtomic(int $productId, float $quantity): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET current_stock = current_stock + :qty, updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $productId, 'qty' => $quantity]);
    }
}
