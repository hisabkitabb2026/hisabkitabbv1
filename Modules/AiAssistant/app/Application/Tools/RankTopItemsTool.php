<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;
use Modules\AiAssistant\Application\Tools\Concerns\ResolvesPeriod;

final class RankTopItemsTool extends AiTool
{
    use ResolvesPeriod;

    private const METRICS = ['quantity_sold', 'revenue'];

    private const DEFAULT_LIMIT = 5;

    private const MAX_LIMIT = 20;

    public function __construct(private readonly CompanyDataReader $data) {}

    public function name(): string
    {
        return 'rank_top_items';
    }

    public function description(): string
    {
        return "Rank catalog items by quantity_sold or revenue over a named time period. Use this when the user asks 'what's our best-selling item', 'most popular products', 'which items brought in the most money', or similar.";
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => ['metric' => ['type' => 'string', 'enum' => self::METRICS, 'description' => 'Which dimension to rank by.'], 'period' => ['type' => 'string', 'enum' => self::ALL_PERIODS, 'description' => 'Named time window. Use all_time for lifetime rankings.'], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_LIMIT, 'description' => 'Max number of items to return. Default 5.']], 'required' => ['metric']];
    }

    public function requiredAbility(): ?array
    {
        return ['view-item', 'item'];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        $metric = (string) ($arguments['metric'] ?? 'revenue');
        if (! in_array($metric, self::METRICS, true)) {
            return ['error' => 'invalid_metric', 'valid' => self::METRICS];
        }

        $period = (string) ($arguments['period'] ?? 'all_time');
        if (! in_array($period, self::ALL_PERIODS, true)) {
            return ['error' => 'invalid_period', 'valid' => self::ALL_PERIODS];
        }

        $limit = min(max((int) ($arguments['limit'] ?? self::DEFAULT_LIMIT), 1), self::MAX_LIMIT);
        $range = $this->rangeFor($period);
        $start = $range === null ? null : $range[0]->toDateString();
        $end = $range === null ? null : $range[1]->toDateString();

        return ['metric' => $metric, 'period' => $period, 'items' => $this->data->rankItems($companyId, $metric, $start, $end, $limit)];
    }
}
