<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;

final class GetInvoiceTool extends AiTool
{
    public function __construct(private readonly CompanyDataReader $data) {}

    public function name(): string
    {
        return 'get_invoice';
    }

    public function description(): string
    {
        return 'Fetch full details for a single invoice by its invoice_number, including line items, taxes, totals, customer info, and dates. Use this after search_invoices when the user wants more detail on a specific invoice.';
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => ['invoice_number' => ['type' => 'string', 'description' => 'The invoice_number to look up (e.g. "INV-000001").']], 'required' => ['invoice_number']];
    }

    public function requiredAbility(): ?array
    {
        return ['view-invoice', 'invoice'];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        $invoice = $this->data->findInvoice($companyId, (string) ($arguments['invoice_number'] ?? ''));

        return $invoice === null ? ['error' => 'invoice_not_found'] : ['invoice' => $invoice];
    }
}
