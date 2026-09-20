<?php
declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS purchases (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                supplier_id BIGINT UNSIGNED NOT NULL,
                invoice_number VARCHAR(50) NOT NULL,
                purchase_date DATE NOT NULL,
                subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                payment_status ENUM('paid', 'partial', 'due') NOT NULL DEFAULT 'paid',
                payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
                notes TEXT NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_purchase_company_invoice (company_id, invoice_number),
                INDEX idx_purchase_company_date (company_id, purchase_date),
                INDEX idx_purchase_supplier (supplier_id),
                CONSTRAINT fk_purchases_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id) REFERENCES contacts(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS purchase_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                purchase_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NOT NULL,
                quantity DECIMAL(15,3) NOT NULL,
                unit_price DECIMAL(15,2) NOT NULL,
                subtotal DECIMAL(15,2) NOT NULL,
                tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                total DECIMAL(15,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_pitem_purchase (purchase_id),
                INDEX idx_pitem_product (product_id),
                CONSTRAINT fk_pitems_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
                CONSTRAINT fk_pitems_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    }
};
