<?php
declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTOs\QuickSaleDTO;
use App\Core\Database;
use App\Domain\Repositories\IProductRepository;
use PDO;
use Throwable;
use RuntimeException;

/**
 * Enterprise Point of Sale (POS) Service
 * Handles high-speed atomic checkout (e.g. Vadapav ₹20) with stock deduction, double-entry ledger, and receipt generation.
 */
class PosService
{
    private PDO $db;
    private IProductRepository $productRepo;
    private StockService $stockService;
    private AccountingService $accountingService;

    public function __construct(
        IProductRepository $productRepo,
        StockService $stockService,
        AccountingService $accountingService
    ) {
        $this->db = Database::getConnection();
        $this->productRepo = $productRepo;
        $this->stockService = $stockService;
        $this->accountingService = $accountingService;
    }

    /**
     * Execute an ultra-fast atomic quick sale
     */
    public function executeQuickSale(QuickSaleDTO $dto): array
    {
        $this->db->beginTransaction();

        try {
            // 1. Calculate totals
            $subtotal = 0.0;
            $itemsProcessed = [];

            foreach ($dto->items as $item) {
                $product = $this->productRepo->findByCompany($dto->companyId, (int)$item['product_id']);
                if (!$product) {
                    throw new RuntimeException("Product #{$item['product_id']} not found or inactive.");
                }

                $qty = (float)$item['quantity'];
                $price = (float)($item['unit_price'] ?? $product['selling_price']);
                $lineTotal = $qty * $price;
                $subtotal += $lineTotal;

                $itemsProcessed[] = [
                    'product_id'   => $product['id'],
                    'product_name' => $product['name'],
                    'quantity'     => $qty,
                    'unit_price'   => $price,
                    'total'        => $lineTotal,
                ];
            }

            $totalAmount = $subtotal;
            $paidAmount = $dto->paidAmount > 0 ? $dto->paidAmount : $totalAmount;
            $invoiceNumber = 'POS-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            // 2. Insert into `sales` table
            $saleStmt = $this->db->prepare("
                INSERT INTO sales 
                (company_id, customer_id, invoice_number, sale_date, sale_type, subtotal, total_amount, paid_amount, payment_status, payment_method, created_by)
                VALUES 
                (:cid, :cust_id, :inv_num, CURDATE(), 'pos_quick', :subtot, :tot, :paid, 'paid', :method, :user_id)
            ");
            $saleStmt->execute([
                'cid'      => $dto->companyId,
                'cust_id'  => $dto->customerId,
                'inv_num'  => $invoiceNumber,
                'subtot'   => $subtotal,
                'tot'      => $totalAmount,
                'paid'     => $paidAmount,
                'method'   => $dto->paymentMethod,
                'user_id'  => $dto->userId,
            ]);
            $saleId = (int)$this->db->lastInsertId();

            // 3. Insert into `sales_items` & Deduct Stock atomically
            $itemStmt = $this->db->prepare("
                INSERT INTO sales_items (sale_id, product_id, quantity, unit_price, subtotal, total)
                VALUES (:sale_id, :product_id, :qty, :price, :subtot, :tot)
            ");

            foreach ($itemsProcessed as $line) {
                $itemStmt->execute([
                    'sale_id'    => $saleId,
                    'product_id' => $line['product_id'],
                    'qty'        => $line['quantity'],
                    'price'      => $line['unit_price'],
                    'subtot'     => $line['total'],
                    'tot'        => $line['total'],
                ]);

                // Deduct stock and write audit trail
                $this->stockService->deductStock(
                    $dto->companyId,
                    $line['product_id'],
                    $line['quantity'],
                    $line['unit_price'],
                    'pos_sale',
                    $saleId,
                    $dto->userId
                );
            }

            // 4. Record Payment
            $cashAccountId = $this->accountingService->getOrCreateAccount($dto->companyId, 'Cash Counter', 'asset');
            $salesAccountId = $this->accountingService->getOrCreateAccount($dto->companyId, 'POS Sales Revenue', 'revenue');

            $paymentNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $payStmt = $this->db->prepare("
                INSERT INTO payments 
                (company_id, payment_number, payment_type, contact_id, account_id, amount, payment_method, payment_date, reference_no, created_by)
                VALUES 
                (:cid, :pnum, 'receive', :cust_id, :acc_id, :amt, :method, CURDATE(), :ref_no, :user_id)
            ");
            $payStmt->execute([
                'cid'     => $dto->companyId,
                'pnum'    => $paymentNumber,
                'cust_id' => $dto->customerId,
                'acc_id'  => $cashAccountId,
                'amt'     => $paidAmount,
                'method'  => $dto->paymentMethod,
                'ref_no'  => $invoiceNumber,
                'user_id' => $dto->userId,
            ]);

            // 5. Post Balanced Double-Entry Accounting Journal (Debit Cash, Credit Sales)
            $this->accountingService->postJournalEntry(
                $dto->companyId,
                'pos_sale',
                $saleId,
                "Quick Sale {$invoiceNumber}",
                [
                    ['account_id' => $cashAccountId, 'debit' => $paidAmount, 'credit' => 0.0, 'desc' => 'Cash Received'],
                    ['account_id' => $salesAccountId, 'debit' => 0.0, 'credit' => $paidAmount, 'desc' => 'POS Sales Revenue'],
                ],
                $dto->userId
            );

            // Commit atomic transaction
            $this->db->commit();

            return [
                'sale_id'        => $saleId,
                'invoice_number' => $invoiceNumber,
                'sale_date'      => date('Y-m-d'),
                'total_amount'   => $totalAmount,
                'paid_amount'    => $paidAmount,
                'payment_method' => $dto->paymentMethod,
                'items'          => $itemsProcessed,
                'receipt_data'   => [
                    'title'       => 'TAX INVOICE',
                    'invoice_no'  => $invoiceNumber,
                    'date'        => date('d-m-Y H:i:s'),
                    'total'       => number_format($totalAmount, 2),
                    'items_count' => count($itemsProcessed),
                ]
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
