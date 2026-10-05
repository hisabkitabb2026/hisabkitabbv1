<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Tests\Unit;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;
use Modules\AiAssistant\Application\Tools\GetCompanyStatsTool;
use Modules\AiAssistant\Application\Tools\RankTopCustomersTool;
use Modules\AiAssistant\Application\Tools\RankTopItemsTool;
use PHPUnit\Framework\TestCase;

final class ToolDelegationTest extends TestCase
{
    public function test_stats_preserve_period_metadata_and_dashboard_ability(): void
    {
        $tool = new GetCompanyStatsTool(new RecordingDataReader);
        $result = $tool->execute(['period' => 'this_month'], 42, 7);
        self::assertSame(['dashboard', null], $tool->requiredAbility());
        self::assertSame('this_month', $result['period']);
        self::assertArrayHasKey('invoices', $result);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result['start']);
    }

    public function test_customer_ranking_uses_supported_metric_envelope_and_current_snapshot(): void
    {
        $reader = new RecordingDataReader;
        $tool = new RankTopCustomersTool($reader);
        $result = $tool->execute(['metric' => 'paid_total', 'period' => 'last_year', 'limit' => 99], 3, 8);
        self::assertSame('paid_total', $result['metric']);
        self::assertSame('last_year', $result['period']);
        self::assertSame(20, $reader->limit);
        self::assertNotNull($reader->start);
        self::assertSame(['error' => 'invalid_metric', 'valid' => ['invoiced_total', 'paid_total', 'invoice_count', 'outstanding_balance']], $tool->execute(['metric' => 'invoice_total'], 3, 8));
        $tool->execute(['metric' => 'outstanding_balance', 'period' => 'this_month'], 3, 8);
        self::assertNull($reader->start);
    }

    public function test_item_ranking_rejects_old_metric_and_uses_named_range(): void
    {
        $reader = new RecordingDataReader;
        $tool = new RankTopItemsTool($reader);
        self::assertSame(['error' => 'invalid_metric', 'valid' => ['quantity_sold', 'revenue']], $tool->execute(['metric' => 'quantity'], 3, 8));
        $result = $tool->execute(['metric' => 'quantity_sold', 'period' => 'this_week'], 3, 8);
        self::assertSame('this_week', $result['period']);
        self::assertNotNull($reader->start);
    }
}

final class RecordingDataReader implements CompanyDataReader
{
    public ?string $start = null;

    public ?string $end = null;

    public int $limit = 0;

    public function companyStats(int $companyId, string $startDate, string $endDate): array
    {
        return ['invoices' => [], 'payments' => [], 'expenses' => []];
    }

    public function findCustomer(int $companyId, int $customerId): ?array
    {
        return null;
    }

    public function searchCustomers(int $companyId, ?string $query, int $limit): array
    {
        return [];
    }

    public function rankCustomers(int $companyId, string $metric, ?string $startDate, ?string $endDate, int $limit): array
    {
        $this->start = $startDate;
        $this->end = $endDate;
        $this->limit = $limit;

        return [];
    }

    public function findInvoice(int $companyId, string $invoiceNumber): ?array
    {
        return null;
    }

    public function searchInvoices(int $companyId, ?string $query, ?string $status, ?int $customerId, int $limit): array
    {
        return [];
    }

    public function overdueInvoices(int $companyId, int $limit): array
    {
        return [];
    }

    public function recentPayments(int $companyId, string $startDate, int $limit): array
    {
        return [];
    }

    public function expenseCategories(int $companyId): array
    {
        return [];
    }

    public function rankExpenseCategories(int $companyId, ?string $startDate, ?string $endDate, int $limit): array
    {
        return [];
    }

    public function searchItems(int $companyId, ?string $query, int $limit): array
    {
        return [];
    }

    public function rankItems(int $companyId, string $metric, ?string $startDate, ?string $endDate, int $limit): array
    {
        $this->start = $startDate;
        $this->end = $endDate;
        $this->limit = $limit;

        return [];
    }

    public function companyMembers(int $companyId): array
    {
        return [];
    }

    public function existingInvoiceIds(int $companyId, array $invoiceIds): array
    {
        return [];
    }
}
