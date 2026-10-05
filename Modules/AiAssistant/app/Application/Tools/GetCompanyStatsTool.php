<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;
use Modules\AiAssistant\Application\Tools\Concerns\ResolvesPeriod;

final class GetCompanyStatsTool extends AiTool
{
    use ResolvesPeriod;

    private const PERIODS = ['today', 'this_week', 'this_month', 'last_month', 'this_quarter', 'this_year', 'last_year'];

    public function __construct(private readonly CompanyDataReader $data) {}

    public function name(): string
    {
        return 'get_company_stats';
    }

    public function description(): string
    {
        return "Aggregate stats for the current company over a named time period: invoice count and total, payment count and total, expense count and total. Use this for 'how much did we earn/spend' questions.";
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => ['period' => ['type' => 'string', 'enum' => self::PERIODS, 'description' => 'Named time window.']], 'required' => ['period']];
    }

    public function requiredAbility(): ?array
    {
        return ['dashboard', null];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        $period = (string) ($arguments['period'] ?? 'this_month');
        if (! in_array($period, self::PERIODS, true)) {
            return ['error' => 'invalid_period', 'valid' => self::PERIODS];
        }

        [$start, $end] = $this->rangeFor($period);

        return ['period' => $period, 'start' => $start->toDateString(), 'end' => $end->toDateString(), ...$this->data->companyStats($companyId, $start->toDateString(), $end->toDateString())];
    }
}
