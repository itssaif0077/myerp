<?php
declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS stock_transactions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NOT NULL,
                transaction_type ENUM('purchase', 'sale', 'adjustment_add', 'adjustment_sub', 'return') NOT NULL,
                quantity DECIMAL(15,3) NOT NULL,
                unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                reference_type VARCHAR(50) NULL,
                reference_id BIGINT UNSIGNED NULL,
                notes VARCHAR(255) NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_stock_product (product_id),
                INDEX idx_stock_company (company_id),
                INDEX idx_stock_ref (reference_type, reference_id),
                CONSTRAINT fk_stock_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    }
};
