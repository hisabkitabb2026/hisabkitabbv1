<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;
use Modules\AiAssistant\Application\Tools\Concerns\ResolvesPeriod;

final class RankExpenseCategoriesTool extends AiTool
{
    use ResolvesPeriod;

    private const DEFAULT_LIMIT = 10;

    private const MAX_LIMIT = 20;

    public function __construct(private readonly CompanyDataReader $data) {}

    public function name(): string
    {
        return 'rank_expense_categories';
    }

    public function description(): string
    {
        return "Rank expense categories by total spend over a named time period. Use this when the user asks 'what are we spending the most on', 'top expense categories', or 'where is the money going'.";
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => ['period' => ['type' => 'string', 'enum' => self::ALL_PERIODS, 'description' => 'Named time window. Use all_time for lifetime totals.'], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_LIMIT, 'description' => 'Max number of categories to return. Default 10.']], 'required' => []];
    }

    public function requiredAbility(): ?array
    {
        return ['view-expense', 'expense'];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        $period = (string) ($arguments['period'] ?? 'all_time');
        if (! in_array($period, self::ALL_PERIODS, true)) {
            return ['error' => 'invalid_period', 'valid' => self::ALL_PERIODS];
        }

        $limit = min(max((int) ($arguments['limit'] ?? self::DEFAULT_LIMIT), 1), self::MAX_LIMIT);
        $range = $this->rangeFor($period);
        $start = $range === null ? null : $range[0]->toDateString();
        $end = $range === null ? null : $range[1]->toDateString();

        return ['period' => $period, 'categories' => $this->data->rankExpenseCategories($companyId, $start, $end, $limit)];
    }
}
