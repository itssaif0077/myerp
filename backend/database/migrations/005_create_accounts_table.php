<?php
declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS accounts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                account_name VARCHAR(150) NOT NULL,
                account_code VARCHAR(50) NULL,
                account_group ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
                parent_id BIGINT UNSIGNED NULL,
                opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                current_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_account_company (company_id),
                INDEX idx_account_group (company_id, account_group),
                CONSTRAINT fk_accounts_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
                CONSTRAINT fk_accounts_parent FOREIGN KEY (parent_id) REFERENCES accounts(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    }
};
