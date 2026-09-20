<?php
declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS sales (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                customer_id BIGINT UNSIGNED NULL,
                invoice_number VARCHAR(50) NOT NULL,
                sale_date DATE NOT NULL,
                sale_type ENUM('standard', 'pos_quick') NOT NULL DEFAULT 'standard',
                subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                discount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                payment_status ENUM('paid', 'partial', 'due') NOT NULL DEFAULT 'paid',
                payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
                notes TEXT NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_sale_company_invoice (company_id, invoice_number),
                INDEX idx_sale_company_date (company_id, sale_date),
                INDEX idx_sale_customer (customer_id),
                CONSTRAINT fk_sales_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES contacts(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS sales_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sale_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NOT NULL,
                quantity DECIMAL(15,3) NOT NULL,
                unit_price DECIMAL(15,2) NOT NULL,
                subtotal DECIMAL(15,2) NOT NULL,
                tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                total DECIMAL(15,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_sitem_sale (sale_id),
                INDEX idx_sitem_product (product_id),
                CONSTRAINT fk_sitems_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
                CONSTRAINT fk_sitems_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    }
};
