<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Application\DTOs\QuickSaleDTO;
use App\Application\Services\PosService;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Repositories\IProductRepository;
use InvalidArgumentException;
use Throwable;

/**
 * Enterprise Point of Sale Controller
 * Thin controller managing POS Quick-Sell tile catalog and instant checkout endpoints.
 */
class PosController
{
    private IProductRepository $productRepo;
    private PosService $posService;

    public function __construct(IProductRepository $productRepo, PosService $posService)
    {
        $this->productRepo = $productRepo;
        $this->posService = $posService;
    }

    /**
     * GET /api/v1/pos/products
     * Return active Quick-Sell product tiles (e.g., Vadapav ₹20)
     */
    public function getQuickProducts(Request $request): void
    {
        $companyId = (int)($request->getAttribute('auth_company_id') ?? $request->query('company_id', 1));
        $products = $this->productRepo->getQuickSellProducts($companyId);

        Response::ok($products, 'Quick-Sell products fetched successfully');
    }

    /**
     * POST /api/v1/pos/sale
     * Process high-speed checkout, stock deduction, accounting ledger, and return receipt
     */
    public function createQuickSale(Request $request): void
    {
        $companyId = (int)($request->getAttribute('auth_company_id') ?? $request->input('company_id', 1));
        $userId = $request->getAttribute('auth_user_id') ? (int)$request->getAttribute('auth_user_id') : null;

        try {
            $dto = QuickSaleDTO::fromRequest($request->all(), $companyId, $userId);
            $result = $this->posService->executeQuickSale($dto);

            Response::created($result, 'Sale completed successfully');
        } catch (InvalidArgumentException $e) {
            Response::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            Response::serverError($e->getMessage());
        }
    }
}
