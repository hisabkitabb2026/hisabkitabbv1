<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;

final class SearchCustomersTool extends AiTool
{
    private const DEFAULT_LIMIT = 10;

    private const MAX_LIMIT = 50;

    public function __construct(private readonly CompanyDataReader $data) {}

    public function name(): string
    {
        return 'search_customers';
    }

    public function description(): string
    {
        return 'Search customers for the current company by free-text query (matches name, display_name, email, company_name, contact_name). Returns a compact list with ids, names, and contact info.';
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => ['query' => ['type' => 'string', 'description' => 'Free-text search against name, email, and related fields.'], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_LIMIT]], 'required' => []];
    }

    public function requiredAbility(): ?array
    {
        return ['view-customer', 'customer'];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        $limit = min((int) ($arguments['limit'] ?? self::DEFAULT_LIMIT), self::MAX_LIMIT);

        return ['customers' => $this->data->searchCustomers($companyId, empty($arguments['query']) ? null : (string) $arguments['query'], $limit)];
    }
}
