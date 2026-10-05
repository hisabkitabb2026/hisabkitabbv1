<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;

final class ListOverdueInvoicesTool extends AiTool
{
    public function __construct(private readonly CompanyDataReader $data) {}

    public function name(): string
    {
        return 'list_overdue_invoices';
    }

    public function description(): string
    {
        return 'List all invoices for the current company that are currently overdue (past their due date and unpaid or partially paid). Sorted by oldest-due-first.';
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => (object) [], 'required' => []];
    }

    public function requiredAbility(): ?array
    {
        return ['view-invoice', 'invoice'];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        $invoices = $this->data->overdueInvoices($companyId, 100);

        return ['count' => count($invoices), 'total_outstanding' => (float) array_sum(array_map(static fn (array $invoice): float => (float) ($invoice['due_amount'] ?? 0), $invoices)), 'invoices' => $invoices];
    }
}
