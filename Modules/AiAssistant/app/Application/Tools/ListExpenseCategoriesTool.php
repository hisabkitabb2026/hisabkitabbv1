<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;

final class ListExpenseCategoriesTool extends AiTool
{
    public function __construct(private readonly CompanyDataReader $data) {}

    public function name(): string
    {
        return 'list_expense_categories';
    }

    public function description(): string
    {
        return 'List all expense categories defined for the current company.';
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => (object) [], 'required' => []];
    }

    public function requiredAbility(): ?array
    {
        return ['view-expense', 'expense'];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        return ['categories' => $this->data->expenseCategories($companyId)];
    }
}
