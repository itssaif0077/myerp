<?php
declare(strict_types=1);

namespace App\Application\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Enterprise Accounting Service
 * Automatically creates balanced double-entry vouchers (Debits = Credits) in journal_entries and journal_entry_items.
 */
class AccountingService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Find or create standard ledger account (e.g. Cash, Sales Revenue)
     */
    public function getOrCreateAccount(int $companyId, string $accountName, string $accountGroup): int
    {
        $stmt = $this->db->prepare("SELECT id FROM accounts WHERE company_id = :cid AND account_name = :name LIMIT 1");
        $stmt->execute(['cid' => $companyId, 'name' => $accountName]);
        $id = $stmt->fetchColumn();

        if ($id) {
            return (int)$id;
        }

        $insertStmt = $this->db->prepare("
            INSERT INTO accounts (company_id, account_name, account_group, is_active)
            VALUES (:cid, :name, :group, 1)
        ");
        $insertStmt->execute(['cid' => $companyId, 'name' => $accountName, 'group' => $accountGroup]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Record a balanced double-entry journal voucher
     * $lines is an array of ['account_id' => int, 'debit' => float, 'credit' => float, 'desc' => string]
     */
    public function postJournalEntry(
        int $companyId,
        string $referenceType,
        int $referenceId,
        string $narration,
        array $lines,
        ?int $userId = null
    ): int {
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $line) {
            $totalDebit += (float)($line['debit'] ?? 0.0);
            $totalCredit += (float)($line['credit'] ?? 0.0);
        }

        // Strict double-entry accounting rule: Debits MUST equal Credits
        if (abs($totalDebit - $totalCredit) > 0.001) {
            throw new RuntimeException("Unbalanced journal entry! Debits ({$totalDebit}) != Credits ({$totalCredit}).");
        }

        $entryNumber = 'JV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

        $stmt = $this->db->prepare("
            INSERT INTO journal_entries 
            (company_id, entry_number, entry_date, reference_type, reference_id, total_debit, total_credit, narration, created_by)
            VALUES 
            (:cid, :entry_num, CURDATE(), :ref_type, :ref_id, :tot_deb, :tot_cred, :narration, :user_id)
        ");
        $stmt->execute([
            'cid'        => $companyId,
            'entry_num'  => $entryNumber,
            'ref_type'   => $referenceType,
            'ref_id'     => $referenceId,
            'tot_deb'    => $totalDebit,
            'tot_cred'   => $totalCredit,
            'narration'  => $narration,
            'user_id'    => $userId
        ]);

        $entryId = (int)$this->db->lastInsertId();

        $itemStmt = $this->db->prepare("
            INSERT INTO journal_entry_items 
            (journal_entry_id, account_id, debit, credit, description)
            VALUES 
            (:entry_id, :account_id, :debit, :credit, :description)
        ");

        foreach ($lines as $line) {
            $itemStmt->execute([
                'entry_id'    => $entryId,
                'account_id'  => $line['account_id'],
                'debit'       => $line['debit'] ?? 0.0,
                'credit'      => $line['credit'] ?? 0.0,
                'description' => $line['desc'] ?? null
            ]);
        }

        return $entryId;
    }
}
