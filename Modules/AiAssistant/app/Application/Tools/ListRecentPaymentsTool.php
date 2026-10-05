<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Application\Tools;

use Carbon\Carbon;
use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;

final class ListRecentPaymentsTool extends AiTool
{
    private const DEFAULT_DAYS = 30;

    private const MAX_DAYS = 365;

    private const DEFAULT_LIMIT = 20;

    private const MAX_LIMIT = 100;

    public function __construct(private readonly CompanyDataReader $data) {}

    public function name(): string
    {
        return 'list_recent_payments';
    }

    public function description(): string
    {
        return 'List payments received in the last N days for the current company, sorted most recent first. Returns payment number, customer, amount, allocation breakdown, unapplied credit, payment date, and payment method.';
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => ['days' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_DAYS, 'description' => 'How many days back to look (default 30, max 365).'], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_LIMIT, 'description' => 'Max rows to return (default 20, max 100).']], 'required' => []];
    }

    public function requiredAbility(): ?array
    {
        return ['view-payment', 'payment'];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        $days = min((int) ($arguments['days'] ?? self::DEFAULT_DAYS), self::MAX_DAYS);
        $limit = min((int) ($arguments['limit'] ?? self::DEFAULT_LIMIT), self::MAX_LIMIT);
        $since = Carbon::now()->subDays($days)->startOfDay()->toDateString();

        return ['since' => $since, 'payments' => $this->data->recentPayments($companyId, $since, $limit)];
    }
}
