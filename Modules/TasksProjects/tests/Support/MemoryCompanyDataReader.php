<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Support;

use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;

/**
 * An in-memory stand-in for the host company reader.
 *
 * Only the surfaces this module actually uses carry state: the invoice ids
 * that still exist, which decide whether a stamped entry counts as billed, the
 * company member list, which supplies names for the member grouping, and the
 * customers, which lend a new project its currency and give the demo seeder
 * its projects. The rest satisfy the contract and return nothing.
 */
final class MemoryCompanyDataReader implements CompanyDataReader
{
    /** @var array<int, list<int>> invoice ids that exist, keyed by company */
    public array $invoiceIds = [];

    /** @var array<int, list<array{id: int, name: string, email: string, avatar: string|null}>> */
    public array $members = [];

    /** @var array<int, array<int, array<string, mixed>>> customers, keyed by company then id */
    public array $customers = [];

    /** @var list<array{company_id: int, invoice_ids: list<int>}> */
    public array $invoiceLookups = [];

    public function withInvoices(int $companyId, int ...$invoiceIds): self
    {
        $this->invoiceIds[$companyId] = array_values($invoiceIds);

        return $this;
    }

    public function withMember(int $companyId, int $userId, string $name): self
    {
        $this->members[$companyId][] = [
            'id' => $userId,
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.test',
            'avatar' => null,
        ];

        return $this;
    }

    public function withCustomer(int $companyId, int $customerId, ?int $currencyId = null): self
    {
        $this->customers[$companyId][$customerId] = [
            'id' => $customerId,
            'name' => 'Customer '.$customerId,
            'currency_id' => $currencyId,
            'currency' => $currencyId === null
                ? null
                : ['id' => $currencyId, 'code' => 'EUR', 'symbol' => 'E', 'precision' => 2],
        ];

        return $this;
    }

    /** @return array<string, mixed> */
    public function companyStats(int $companyId, string $startDate, string $endDate): array
    {
        return [];
    }

    /** @return array<string, mixed>|null */
    public function findCustomer(int $companyId, int $customerId): ?array
    {
        return $this->customers[$companyId][$customerId] ?? null;
    }

    /** @return array<string, mixed> */
    public function searchCustomers(int $companyId, ?string $query, int $limit): array
    {
        $customers = array_values($this->customers[$companyId] ?? []);
        usort($customers, fn (array $a, array $b): int => [$a['name'], $a['id']] <=> [$b['name'], $b['id']]);

        return array_slice($customers, 0, $limit);
    }

    /** @return array<string, mixed> */
    public function rankCustomers(int $companyId, string $metric, ?string $startDate, ?string $endDate, int $limit): array
    {
        return [];
    }

    /** @return array<string, mixed>|null */
    public function findInvoice(int $companyId, string $invoiceNumber): ?array
    {
        return null;
    }

    /** @return array<string, mixed> */
    public function searchInvoices(int $companyId, ?string $query, ?string $status, ?int $customerId, int $limit): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function overdueInvoices(int $companyId, int $limit): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function recentPayments(int $companyId, string $startDate, int $limit): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function expenseCategories(int $companyId): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function rankExpenseCategories(int $companyId, ?string $startDate, ?string $endDate, int $limit): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function searchItems(int $companyId, ?string $query, int $limit): array
    {
        return [];
    }

    /** @return array<string, mixed> */
    public function rankItems(int $companyId, string $metric, ?string $startDate, ?string $endDate, int $limit): array
    {
        return [];
    }

    /** @return list<array{id: int, name: string, email: string, avatar: string|null}> */
    public function companyMembers(int $companyId): array
    {
        return $this->members[$companyId] ?? [];
    }

    /**
     * @param  list<int>  $invoiceIds
     * @return list<int>
     */
    public function existingInvoiceIds(int $companyId, array $invoiceIds): array
    {
        $this->invoiceLookups[] = ['company_id' => $companyId, 'invoice_ids' => array_values($invoiceIds)];

        return array_values(array_intersect($invoiceIds, $this->invoiceIds[$companyId] ?? []));
    }
}
