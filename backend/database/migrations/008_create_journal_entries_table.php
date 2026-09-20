<?php
declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS journal_entries (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                company_id BIGINT UNSIGNED NOT NULL,
                entry_number VARCHAR(50) NOT NULL,
                entry_date DATE NOT NULL,
                reference_type VARCHAR(50) NULL,
                reference_id BIGINT UNSIGNED NULL,
                total_debit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                total_credit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                narration TEXT NULL,
                created_by BIGINT UNSIGNED NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_journal_company_number (company_id, entry_number),
                INDEX idx_journal_company_date (company_id, entry_date),
                CONSTRAINT fk_journal_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS journal_entry_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                journal_entry_id BIGINT UNSIGNED NOT NULL,
                account_id BIGINT UNSIGNED NOT NULL,
                debit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                credit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                description VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_jitem_entry (journal_entry_id),
                INDEX idx_jitem_account (account_id),
                CONSTRAINT fk_jitem_entry FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
                CONSTRAINT fk_jitem_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sql);
    }
};
