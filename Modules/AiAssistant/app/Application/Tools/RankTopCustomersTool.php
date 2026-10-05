<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;
use Modules\AiAssistant\Application\Tools\Concerns\ResolvesPeriod;

final class RankTopCustomersTool extends AiTool
{
    use ResolvesPeriod;

    private const METRICS = ['invoiced_total', 'paid_total', 'invoice_count', 'outstanding_balance'];

    private const DEFAULT_LIMIT = 5;

    private const MAX_LIMIT = 20;

    public function __construct(private readonly CompanyDataReader $data) {}

    public function name(): string
    {
        return 'rank_top_customers';
    }

    public function description(): string
    {
        return "Rank customers by a business metric (invoiced_total, paid_total, invoice_count, or outstanding_balance). Use this when the user asks 'who are our top customers', 'who did the most business with us', 'who owes us the most', or similar ranking questions. outstanding_balance ignores the period — it's always the current snapshot.";
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => ['metric' => ['type' => 'string', 'enum' => self::METRICS, 'description' => 'Which metric to rank by.'], 'period' => ['type' => 'string', 'enum' => self::ALL_PERIODS, 'description' => 'Named time window. Use all_time for lifetime rankings. Ignored for outstanding_balance (always current).'], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_LIMIT, 'description' => 'Max number of customers to return. Default 5.']], 'required' => ['metric']];
    }

    public function requiredAbility(): ?array
    {
        return ['view-customer', 'customer'];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        $metric = (string) ($arguments['metric'] ?? 'invoiced_total');
        if (! in_array($metric, self::METRICS, true)) {
            return ['error' => 'invalid_metric', 'valid' => self::METRICS];
        }

        $period = (string) ($arguments['period'] ?? 'all_time');
        if (! in_array($period, self::ALL_PERIODS, true)) {
            return ['error' => 'invalid_period', 'valid' => self::ALL_PERIODS];
        }

        $limit = min(max((int) ($arguments['limit'] ?? self::DEFAULT_LIMIT), 1), self::MAX_LIMIT);
        $range = $metric === 'outstanding_balance' ? null : $this->rangeFor($period);
        $start = $range === null ? null : $range[0]->toDateString();
        $end = $range === null ? null : $range[1]->toDateString();

        return ['metric' => $metric, 'period' => $metric === 'outstanding_balance' ? 'current' : $period, 'customers' => $this->data->rankCustomers($companyId, $metric, $start, $end, $limit)];
    }
}
