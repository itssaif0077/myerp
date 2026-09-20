<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Application\DTOs\ContactDTO;
use App\Application\Services\ContactService;
use App\Core\Request;
use App\Core\Response;
use InvalidArgumentException;
use Throwable;

/**
 * Enterprise Contact Controller
 * Manages Customers, Suppliers, Karigars, Staff, and Brokers across multiple firms.
 */
class ContactController
{
    private ContactService $contactService;

    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    /**
     * Resolve active firm/company ID
     * Priority: 1. X-Company-Id Header -> 2. Query/Body company_id -> 3. Token auth_company_id
     */
    private function resolveCompanyId(Request $request): int
    {
        $headerCompany = $request->getHeader('x-company-id');
        if ($headerCompany && is_numeric($headerCompany)) {
            return (int)$headerCompany;
        }

        $inputCompany = $request->input('company_id') ?? $request->input('firm_id') ?? $request->query('company_id') ?? $request->query('firm_id');
        if ($inputCompany && is_numeric($inputCompany)) {
            return (int)$inputCompany;
        }

        return (int)($request->getAttribute('auth_company_id') ?? 1);
    }

    /**
     * GET or POST /api/v1/contacts
     * Query contacts by type (customer, supplier, karigar, staff), search, date range, and pagination
     */
    public function index(Request $request): void
    {
        $companyId = $this->resolveCompanyId($request);
        $page = max(1, (int)($request->input('page') ?? $request->query('page', 1)));
        $limit = min(200, max(1, (int)($request->input('limit') ?? $request->query('limit', 100))));

        $filters = [
            'type'       => $request->input('type') ?? $request->query('type'),
            'staff_role' => $request->input('staff_role') ?? $request->query('staff_role'),
            'status'     => $request->input('status') ?? $request->query('status', 'active'),
            'search'     => $request->input('search') ?? $request->query('search'),
            'from_date'  => $request->input('from_date') ?? $request->input('date_from') ?? $request->query('from_date'),
            'to_date'    => $request->input('to_date') ?? $request->input('date_to') ?? $request->query('to_date'),
        ];

        try {
            $result = $this->contactService->listContacts($companyId, $filters, $page, $limit);
            Response::ok($result, 'Contacts fetched successfully');
        } catch (Throwable $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * GET /api/v1/contacts/{id}
     */
    public function show(Request $request, string $id): void
    {
        $companyId = $this->resolveCompanyId($request);

        try {
            $contact = $this->contactService->getContact($companyId, (int)$id);
            Response::ok($contact, 'Contact retrieved successfully');
        } catch (Throwable $e) {
            Response::notFound($e->getMessage());
        }
    }

    /**
     * POST /api/v1/contacts
     * If name is provided -> creates contact. If name is not provided -> fetches list by type!
     */
    public function store(Request $request): void
    {
        // If no contact name is provided, treat as a list/fetch request by type!
        $name = $request->input('name');
        if ($name === null || trim((string)$name) === '') {
            $this->index($request);
            return;
        }

        $companyId = $this->resolveCompanyId($request);
        $forceCreate = filter_var($request->input('force_create') ?? false, FILTER_VALIDATE_BOOLEAN);

        try {
            $dto = ContactDTO::fromRequest($request->all(), $companyId);
            $contact = $this->contactService->createContact($dto, $forceCreate);

            Response::created($contact, ucfirst($dto->type) . ' created successfully');
        } catch (\App\Application\Exceptions\DuplicateContactException $e) {
            Response::conflict($e->getMessage(), ['existing_contact' => $e->getExistingContact()]);
        } catch (InvalidArgumentException $e) {
            Response::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            Response::serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/v1/contacts/{id}
     */
    public function update(Request $request, string $id): void
    {
        $companyId = $this->resolveCompanyId($request);

        try {
            $updated = $this->contactService->updateContact($companyId, (int)$id, $request->all());
            Response::ok($updated, 'Contact updated successfully');
        } catch (Throwable $e) {
            Response::badRequest($e->getMessage());
        }
    }

    /**
     * DELETE /api/v1/contacts/{id}
     */
    public function destroy(Request $request, string $id): void
    {
        $companyId = $this->resolveCompanyId($request);

        try {
            $this->contactService->deleteContact($companyId, (int)$id);
            Response::ok(null, 'Contact archived successfully');
        } catch (Throwable $e) {
            Response::badRequest($e->getMessage());
        }
    }
}
