<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;

final class SearchInvoicesTool extends AiTool
{
    private const DEFAULT_LIMIT = 10;

    private const MAX_LIMIT = 50;

    public function __construct(private readonly CompanyDataReader $data) {}

    public function name(): string
    {
        return 'search_invoices';
    }

    public function description(): string
    {
        return 'Search invoices for the current company. Filter by free-text query (matches invoice number and reference), status, or customer_id. Returns a compact list with invoice numbers, customers, dates, totals, and statuses.';
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => ['query' => ['type' => 'string', 'description' => 'Optional free-text search against invoice_number and reference_number.'], 'status' => ['type' => 'string', 'enum' => ['DRAFT', 'SENT', 'VIEWED', 'COMPLETED', 'UNPAID', 'PARTIALLY_PAID', 'PAID', 'OVERDUE'], 'description' => 'Optional status filter.'], 'customer_id' => ['type' => 'integer', 'description' => 'Optional customer ID to restrict to a specific customer.'], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_LIMIT, 'description' => 'Max rows to return (default 10, max 50).']], 'required' => []];
    }

    public function requiredAbility(): ?array
    {
        return ['view-invoice', 'invoice'];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        $limit = min((int) ($arguments['limit'] ?? self::DEFAULT_LIMIT), self::MAX_LIMIT);

        return ['invoices' => $this->data->searchInvoices($companyId, empty($arguments['query']) ? null : (string) $arguments['query'], empty($arguments['status']) ? null : (string) $arguments['status'], empty($arguments['customer_id']) ? null : (int) $arguments['customer_id'], $limit)];
    }
}
