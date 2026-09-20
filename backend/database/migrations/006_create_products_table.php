<?php
declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS products (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(200) NOT NULL,
                sku VARCHAR(100) NULL,
                barcode VARCHAR(100) NULL,
                category VARCHAR(100) NULL DEFAULT 'General',
                unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
                purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                selling_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                current_stock DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                alert_quantity DECIMAL(15,3) NOT NULL DEFAULT 0.000,
                is_quick_sell TINYINT(1) NOT NULL DEFAULT 0,
                quick_sell_order INT NOT NULL DEFAULT 0,
                image_url VARCHAR(255) NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_product_company (company_id),
                INDEX idx_product_quick_sell (company_id, is_quick_sell, quick_sell_order),
                INDEX idx_product_barcode (company_id, barcode),
                INDEX idx_product_sku (company_id, sku),
                CONSTRAINT fk_products_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    }
};
