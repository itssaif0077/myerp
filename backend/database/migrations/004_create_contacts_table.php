<?php
declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS contacts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                type ENUM('customer', 'supplier', 'staff') NOT NULL,
                staff_role ENUM('none', 'admin', 'manager', 'salesman', 'accountant') NOT NULL DEFAULT 'none',
                name VARCHAR(200) NOT NULL,
                company_name VARCHAR(200) NULL,
                email VARCHAR(150) NULL,
                phone VARCHAR(20) NULL,
                gst_vat_number VARCHAR(50) NULL,
                address TEXT NULL,
                opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                balance_type ENUM('debit', 'credit') NOT NULL DEFAULT 'debit',
                current_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_contact_company_type (company_id, type),
                INDEX idx_contact_phone (phone),
                CONSTRAINT fk_contacts_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    }
};
