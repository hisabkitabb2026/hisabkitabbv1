<?php

declare(strict_types=1);

namespace Modules\Trips\Application;

use App\Domains\Purchases\Models\Bill;
use App\Domains\Purchases\Models\Supplier;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripReceipt;
use Modules\Trips\Models\TripStatus;

/**
 * Read-only aggregates over trips, for the reports page.
 *
 * Every total is in integer minor units, the same unit TripService::money()
 * works in. The range filters on the trip's pickup date, falling back to the
 * created date when the pickup date is empty, so a trip that was logged
 * without one is not silently dropped from a monthly report.
 */
final class TripReportService
{
    public function __construct(private readonly TripService $trips) {}

    /**
     * @param  string  $from  inclusive start date, Y-m-d
     * @param  string  $to  inclusive end date, Y-m-d
     * @param  array{customer?: ?string, lr_no?: ?string, invoice_no?: ?string, supplier?: ?string}  $filters
     * @return array{from: string, to: string, totals: array<string, mixed>, by_status: list<array<string, mixed>>, by_customer: list<array<string, mixed>>, by_route: list<array<string, mixed>>}
     */
    public function summary(int $companyId, string $from, string $to, array $filters = []): array
    {
        $trips = $this->filteredTrips($companyId, $from, $to, $filters);

        $statuses = TripStatus::query()
            ->forCompany($companyId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        return [
            'from' => $from,
            'to' => $to,
            'totals' => $this->totals($trips),
            'by_status' => $this->byStatus($trips, $statuses),
            'by_customer' => $this->byCustomer($trips),
            'by_route' => $this->byRoute($trips),
        ];
    }

    /** @param  Collection<int, Trip>  $trips */
    private function totals(Collection $trips): array
    {
        $revenue = 0;
        $cost = 0;
        $unbilled = 0;
        $count = $trips->count();

        foreach ($trips as $trip) {
            $money = $this->trips->money($trip);
            $revenue += $money['revenue'];
            $cost += $money['cost'];

            $lrs = $trip->receipts->where('type', TripReceipt::TYPE_LR);

            if ($lrs->isNotEmpty() && $lrs->every(fn ($r) => $r->billed_invoice_id === null)) {
                $unbilled += $money['revenue'];
            }
        }

        return [
            'trip_count' => $count,
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $revenue - $cost,
            'unbilled_amount' => $unbilled,
        ];
    }

    /**
     * @param  Collection<int, Trip>  $trips
     * @param  Collection<int, TripStatus>  $statuses
     * @return list<array<string, mixed>>
     */
    private function byStatus(Collection $trips, Collection $statuses): array
    {
        $rows = [];

        foreach ($trips as $trip) {
            $statusId = (int) $trip->status_id;
            $status = $statuses->get($statusId);

            if (! isset($rows[$statusId])) {
                $rows[$statusId] = [
                    'status_id' => $statusId,
                    'status_name' => $status?->name ?? "Status #{$statusId}",
                    'status_colour' => $status?->colour,
                    'trip_count' => 0,
                    'revenue' => 0,
                    'cost' => 0,
                    'profit' => 0,
                ];
            }

            $money = $this->trips->money($trip);

            $rows[$statusId]['trip_count']++;
            $rows[$statusId]['revenue'] += $money['revenue'];
            $rows[$statusId]['cost'] += $money['cost'];
            $rows[$statusId]['profit'] += $money['profit'];
        }

        return array_values($rows);
    }

    /** @param  Collection<int, Trip>  $trips */
    private function byCustomer(Collection $trips): array
    {
        $rows = [];

        foreach ($trips as $trip) {
            $lrs = $trip->receipts->where('type', TripReceipt::TYPE_LR);

            if ($lrs->isEmpty()) {
                $this->accumulateCustomer($rows, null, 'No customer', $trip);

                continue;
            }

            $seen = [];

            foreach ($lrs as $receipt) {
                $customerId = $receipt->customer_id;
                $customerName = $receipt->customer_name ?? 'No customer';

                if (in_array($customerId, $seen, true)) {
                    continue;
                }

                $seen[] = $customerId;
                $this->accumulateCustomer($rows, $customerId, $customerName, $trip);
            }
        }

        usort($rows, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return array_values($rows);
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $rows
     */
    private function accumulateCustomer(array &$rows, ?int $customerId, string $customerName, Trip $trip): void
    {
        $key = $customerId ?? 'none';

        if (! isset($rows[$key])) {
            $rows[$key] = [
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'trip_count' => 0,
                'revenue' => 0,
                'cost' => 0,
                'profit' => 0,
            ];
        }

        $money = $this->trips->money($trip);

        $rows[$key]['trip_count']++;
        $rows[$key]['revenue'] += $money['revenue'];
        $rows[$key]['cost'] += $money['cost'];
        $rows[$key]['profit'] += $money['profit'];
    }

    /** @param  Collection<int, Trip>  $trips */
    private function byRoute(Collection $trips): array
    {
        $rows = [];

        foreach ($trips as $trip) {
            $from = $trip->from_city ?? '—';
            $to = $trip->to_city ?? '—';
            $key = "{$from} → {$to}";

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'from_city' => $from,
                    'to_city' => $to,
                    'route' => $key,
                    'trip_count' => 0,
                    'revenue' => 0,
                    'cost' => 0,
                    'profit' => 0,
                ];
            }

            $money = $this->trips->money($trip);

            $rows[$key]['trip_count']++;
            $rows[$key]['revenue'] += $money['revenue'];
            $rows[$key]['cost'] += $money['cost'];
            $rows[$key]['profit'] += $money['profit'];
        }

        usort($rows, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);

        return array_values($rows);
    }

    /**
     * One row per LR receipt, with the trip's money and the receipt's customer
     * and LR number. Used by the PDF report's "By LR No" view.
     *
     * @return list<array<string, mixed>>
     */
    public function byLr(int $companyId, string $from, string $to, array $filters = []): array
    {
        $trips = $this->filteredTrips($companyId, $from, $to, $filters);

        $rows = [];

        foreach ($trips as $trip) {
            $money = $this->trips->money($trip);
            $lrs = $trip->receipts->where('type', TripReceipt::TYPE_LR);

            if ($lrs->isEmpty()) {
                $rows[] = [
                    'lr_no' => null,
                    'customer_name' => 'No customer',
                    'from_city' => $trip->from_city,
                    'to_city' => $trip->to_city,
                    'revenue' => $money['revenue'],
                    'cost' => $money['cost'],
                    'profit' => $money['profit'],
                ];

                continue;
            }

            foreach ($lrs as $receipt) {
                $rows[] = [
                    'lr_no' => $receipt->lr_no,
                    'customer_name' => $receipt->customer_name ?? 'No customer',
                    'from_city' => $trip->from_city,
                    'to_city' => $trip->to_city,
                    'revenue' => $money['revenue'],
                    'cost' => $money['cost'],
                    'profit' => $money['profit'],
                ];
            }
        }

        return $rows;
    }

    /**
     * One row per supplier, aggregating all trips where that supplier's bill
     * is behind a linked lorry receipt.
     *
     * @param  array{customer?: ?string, lr_no?: ?string, invoice_no?: ?string, supplier?: ?string}  $filters
     * @return list<array<string, mixed>>
     */
    public function bySupplier(int $companyId, string $from, string $to, array $filters = []): array
    {
        $trips = $this->filteredTrips($companyId, $from, $to, $filters);

        $rows = [];

        foreach ($trips as $trip) {
            $money = $this->trips->money($trip);
            $payable = $money['payable'] ?? [];

            foreach ($payable as $pay) {
                $name = $pay['supplier_name'] ?? 'Unknown';
                $key = $pay['bill_id'] ?? $name;

                if (! isset($rows[$key])) {
                    $rows[$key] = [
                        'supplier_name' => $name,
                        'bill_number' => $pay['bill_number'] ?? '—',
                        'bill_reference' => $pay['bill_reference'] ?? '—',
                        'trip_count' => 0,
                        'revenue' => 0,
                        'cost' => 0,
                        'profit' => 0,
                    ];
                }

                $rows[$key]['trip_count']++;
                $rows[$key]['revenue'] += $money['revenue'];
                $rows[$key]['cost'] += $money['cost'];
                $rows[$key]['profit'] += $money['profit'];
            }
        }

        usort($rows, fn ($a, $b) => $b['cost'] <=> $a['cost']);

        return array_values($rows);
    }

    /**
     * One row per invoice receipt linked to trips in the range.
     *
     * @param  array{customer?: ?string, lr_no?: ?string, invoice_no?: ?string, supplier?: ?string}  $filters
     * @return list<array<string, mixed>>
     */
    public function byInvoice(int $companyId, string $from, string $to, array $filters = []): array
    {
        $trips = $this->filteredTrips($companyId, $from, $to, $filters);

        $rows = [];

        foreach ($trips as $trip) {
            $money = $this->trips->money($trip);
            $receivable = $money['receivable'] ?? [];

            foreach ($receivable as $rec) {
                $rows[] = [
                    'invoice_number' => $rec['invoice_number'] ?? '—',
                    'customer_name' => $rec['customer_name'] ?? 'No customer',
                    'total' => $rec['total'] ?? 0,
                    'due_amount' => $rec['due_amount'] ?? 0,
                    'paid_status' => $rec['paid_status'] ?? '—',
                    'revenue' => $money['revenue'],
                    'cost' => $money['cost'],
                    'profit' => $money['profit'],
                ];
            }
        }

        return $rows;
    }

    /**
     * Per-trip "The Money" breakdown for the PDF: revenue, cost, profit,
     * receivable (from customer) and payable (to owner) for each trip.
     *
     * @param  array{customer?: ?string, lr_no?: ?string, invoice_no?: ?string, supplier?: ?string}  $filters
     * @return list<array<string, mixed>>
     */
    public function tripDetails(int $companyId, string $from, string $to, array $filters = []): array
    {
        $trips = $this->filteredTrips($companyId, $from, $to, $filters);

        $rows = [];

        foreach ($trips as $trip) {
            $money = $this->trips->money($trip);

            $lrReceipts = $trip->receipts->where('type', TripReceipt::TYPE_LR);
            $lorryReceipts = $trip->receipts->where('type', TripReceipt::TYPE_LORRY);
            $invoiceReceipts = $trip->receipts->where('type', TripReceipt::TYPE_INVOICE);

            $rows[] = [
                'trip_no' => $trip->trip_no,
                'from_city' => $trip->from_city ?? '—',
                'to_city' => $trip->to_city ?? '—',
                'lorry_no' => $trip->lorry_no ?? '—',
                'lr_numbers' => $lrReceipts->isNotEmpty()
                    ? $lrReceipts->map(fn ($r) => $r->invoice_number)->filter()->implode(', ')
                    : '—',
                'lorry_receipt_numbers' => $lorryReceipts->isNotEmpty()
                    ? $lorryReceipts->map(fn ($r) => $r->invoice_number)->filter()->implode(', ')
                    : '—',
                'invoice_numbers' => $invoiceReceipts->isNotEmpty()
                    ? $invoiceReceipts->map(fn ($r) => $r->invoice_number)->filter()->implode(', ')
                    : '—',
                'revenue' => $money['revenue'],
                'cost' => $money['cost'],
                'profit' => $money['profit'],
                'receivable' => $money['receivable'],
                'payable' => $money['payable'],
            ];
        }

        return $rows;
    }

    /**
     * The base query: trips in the date range, with optional text filters
     * for customer name, LR number, invoice number, and supplier name.
     *
     * @param  array{customer?: ?string, lr_no?: ?string, invoice_no?: ?string, supplier?: ?string}  $filters
     * @return Collection<int, Trip>
     */
    private function filteredTrips(int $companyId, string $from, string $to, array $filters = []): Collection
    {
        $query = Trip::query()
            ->where('company_id', $companyId)
            ->whereNull('cancelled_at')
            ->where(function ($q) use ($from, $to): void {
                $q->where(function ($sq) use ($from, $to): void {
                    $sq->whereNotNull('pickup_date')
                        ->where('pickup_date', '>=', Carbon::parse($from)->startOfDay())
                        ->where('pickup_date', '<=', Carbon::parse($to)->endOfDay());
                })->orWhere(function ($sq) use ($from, $to): void {
                    $sq->whereNull('pickup_date')
                        ->where('created_at', '>=', Carbon::parse($from)->startOfDay())
                        ->where('created_at', '<=', Carbon::parse($to)->endOfDay());
                });
            })
            ->with(['receipts', 'expenses', 'status'])
            ->orderByDesc('id');

        $customer = trim((string) ($filters['customer'] ?? ''));
        $lrNo = trim((string) ($filters['lr_no'] ?? ''));
        $invoiceNo = trim((string) ($filters['invoice_no'] ?? ''));
        $supplier = trim((string) ($filters['supplier'] ?? ''));

        if ($customer !== '') {
            $query->whereHas('receipts', function ($q) use ($customer): void {
                $q->where('customer_name', 'LIKE', "%{$customer}%");
            });
        }

        if ($lrNo !== '') {
            $query->whereHas('receipts', function ($q) use ($lrNo): void {
                $q->where('type', TripReceipt::TYPE_LR)
                    ->where('invoice_number', 'LIKE', "%{$lrNo}%");
            });
        }

        if ($invoiceNo !== '') {
            $query->whereHas('receipts', function ($q) use ($invoiceNo): void {
                $q->where('type', TripReceipt::TYPE_INVOICE)
                    ->where('invoice_number', 'LIKE', "%{$invoiceNo}%");
            });
        }

        if ($supplier !== '') {
            $supplierIds = Supplier::query()
                ->where('company_id', $companyId)
                ->where('name', 'LIKE', "%{$supplier}%")
                ->pluck('id');

            if ($supplierIds->isNotEmpty()) {
                $billIds = Bill::query()
                    ->where('company_id', $companyId)
                    ->whereIn('supplier_id', $supplierIds)
                    ->pluck('id');

                if ($billIds->isNotEmpty()) {
                    $query->whereHas('receipts', function ($q) use ($billIds): void {
                        $q->where('type', TripReceipt::TYPE_LORRY)
                            ->whereIn('invoice_id', function ($sq) use ($billIds): void {
                                $sq->select('id')
                                    ->from('invoices')
                                    ->whereIn('tr_bill_id', $billIds);
                            });
                    });
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->get();
    }
}
