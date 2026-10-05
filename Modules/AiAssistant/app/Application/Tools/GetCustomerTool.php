<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;

final class GetCustomerTool extends AiTool
{
    public function __construct(private readonly CompanyDataReader $data) {}

    public function name(): string
    {
        return 'get_customer';
    }

    public function description(): string
    {
        return 'Fetch full details for a single customer by ID, including contact info, billing/shipping address, and aggregate totals (invoice count, outstanding balance).';
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => ['customer_id' => ['type' => 'integer', 'description' => 'The customer ID.']], 'required' => ['customer_id']];
    }

    public function requiredAbility(): ?array
    {
        return ['view-customer', 'customer'];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        $customer = $this->data->findCustomer($companyId, (int) ($arguments['customer_id'] ?? 0));

        return $customer === null ? ['error' => 'customer_not_found'] : ['customer' => $customer];
    }
}
