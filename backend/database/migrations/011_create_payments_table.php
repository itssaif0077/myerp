<?php
declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS payments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                payment_number VARCHAR(50) NOT NULL,
                payment_type ENUM('pay', 'receive') NOT NULL,
                contact_id BIGINT UNSIGNED NULL,
                account_id BIGINT UNSIGNED NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
                payment_date DATE NOT NULL,
                reference_no VARCHAR(100) NULL,
                notes TEXT NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_payment_company_number (company_id, payment_number),
                INDEX idx_payment_company_date (company_id, payment_date),
                INDEX idx_payment_contact (contact_id),
                INDEX idx_payment_account (account_id),
                CONSTRAINT fk_payments_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                CONSTRAINT fk_payments_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
                CONSTRAINT fk_payments_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    }
};
