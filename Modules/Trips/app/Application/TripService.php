<?php

declare(strict_types=1);

namespace Modules\Trips\Application;

use App\Domains\Purchases\Models\Bill;
use App\Domains\Purchases\Models\SupplierPayment;
use App\Domains\Sales\Models\Invoice;
use DateTimeInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripEvent;
use Modules\Trips\Models\TripExpense;
use Modules\Trips\Models\TripReceipt;

/**
 * The trip domain: create, board, edit, link documents, money.
 *
 * The module reads the host Invoice model directly (as the local receipt
 * modules do) but never writes to it: creating an LR or Lorry receipt happens
 * through the host's own invoice endpoint, from the browser, and this service
 * only links the result.
 */
final class TripService
{
    public function __construct(
        private readonly TripStatusService $statuses,
        private readonly TripNumberSequence $sequence,
    ) {}

    /**
     * The board: every trip of the company with its computed money,
     * grouped by nothing (the frontend groups by status).
     *
     * @param  array{search?: ?string, customer_id?: ?int, status_id?: ?int, unbilled_only?: bool}  $filters
     * @return Collection<int, Trip>
     */
    public function board(int $companyId, array $filters = []): Collection
    {
        $this->statuses->ensureDefaults($companyId);

        $query = Trip::query()
            ->where('company_id', $companyId)
            ->with(['receipts', 'expenses', 'status'])
            ->orderBy('board_position');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('from_city', 'like', "%{$search}%")
                    ->orWhere('to_city', 'like', "%{$search}%")
                    ->orWhere('lorry_no', 'like', "%{$search}%")
                    ->orWhere('goods', 'like', "%{$search}%")
                    ->orWhere('trip_no', (int) $search);
            });
        }

        if (! empty($filters['status_id'])) {
            $query->where('status_id', (int) $filters['status_id']);
        }

        if (! empty($filters['customer_id'])) {
            $customerId = (int) $filters['customer_id'];
            $query->whereHas('receipts', fn ($q) => $q->where('customer_id', $customerId));
        }

        if (! empty($filters['unbilled_only'])) {
            $query->whereHas('receipts', fn ($q) => $q->where('type', TripReceipt::TYPE_LR)->whereNull('billed_invoice_id'));
        }

        return $query->get();
    }

    public function findForCompany(int $companyId, int $id): Trip
    {
        /** @var Trip|null $trip */
        $trip = Trip::query()
            ->where('company_id', $companyId)
            ->with(['receipts', 'events', 'expenses', 'status'])
            ->find($id);

        if ($trip === null) {
            throw (new ModelNotFoundException)->setModel(Trip::class, [$id]);
        }

        return $trip;
    }

    /** @param array<string, mixed> $attributes */
    public function create(int $companyId, int $userId, array $attributes): Trip
    {
        return DB::transaction(function () use ($companyId, $userId, $attributes): Trip {
            $status = $this->statuses->defaultFor($companyId);

            $trip = Trip::query()->create([
                'company_id' => $companyId,
                'trip_no' => $this->sequence->next($companyId),
                'status_id' => $status->id,
                'board_position' => $this->endOfColumn($companyId, $status->id),
                'from_city' => $attributes['from_city'] ?? null,
                'to_city' => $attributes['to_city'] ?? null,
                'goods' => $attributes['goods'] ?? null,
                'weight' => $attributes['weight'] ?? null,
                'pickup_date' => $attributes['pickup_date'] ?? null,
                'lorry_no' => $attributes['lorry_no'] ?? null,
                'owner_party_id' => $attributes['owner_party_id'] ?? null,
                'driver_party_id' => $attributes['driver_party_id'] ?? null,
                'broker_party_id' => $attributes['broker_party_id'] ?? null,
            ]);

            $this->log($trip, $userId, 'created', 'Trip created.');

            return $trip;
        });
    }

    /**
     * A trip born from existing LR receipts: the shared-load entry point. The
     * first LR's route and goods seed the trip; every LR is linked.
     *
     * @param  list<int>  $invoiceIds
     */
    public function createFromLrInvoices(int $companyId, int $userId, array $invoiceIds): Trip
    {
        $invoiceIds = array_values(array_unique(array_map(intval(...), $invoiceIds)));

        if ($invoiceIds === []) {
            throw ValidationException::withMessages(['invoice_ids' => 'Select at least one LR receipt.']);
        }

        return DB::transaction(function () use ($companyId, $userId, $invoiceIds): Trip {
            $invoices = Invoice::query()
                ->where('company_id', $companyId)
                ->where('template_name', 'lr_receipt')
                ->whereIn('id', $invoiceIds)
                ->with('customer')
                ->get();

            if ($invoices->count() !== count($invoiceIds)) {
                throw ValidationException::withMessages(['invoice_ids' => 'One of the selected LR receipts was not found.']);
            }

            $alreadyLinked = TripReceipt::query()
                ->where('company_id', $companyId)
                ->whereIn('invoice_id', $invoiceIds)
                ->pluck('invoice_id');

            if ($alreadyLinked->isNotEmpty()) {
                throw ValidationException::withMessages(['invoice_ids' => 'One of the selected LR receipts is already on a trip.']);
            }

            $first = $invoices->first();

            $trip = $this->create($companyId, $userId, [
                'from_city' => $first->tr_from_name,
                'to_city' => $first->tr_to_name,
                'goods' => $first->tr_description_goods,
                'pickup_date' => $first->invoice_date instanceof DateTimeInterface
                ? $first->invoice_date->format('Y-m-d')
                : ($first->invoice_date !== null ? (string) $first->invoice_date : null),
            ]);

            foreach ($invoices as $invoice) {
                $this->linkReceiptRow($trip, $invoice, TripReceipt::TYPE_LR);
            }

            // Auto-match the lorry receipt: the bilty numbers on the selected
            // LRs tell us which lorry carried them.
            $lrNumbers = $invoices->map(fn (Invoice $invoice): string => (string) $invoice->invoice_number)->all();
            $lorryMatches = $this->matchLorryReceipts($companyId, $lrNumbers);

            foreach ($lorryMatches as $lorry) {
                $this->linkReceiptRow($trip, $lorry, TripReceipt::TYPE_LORRY);

                $this->log($trip, $userId, 'receipt_linked', "Lorry Receipt {$lorry->invoice_number} auto-matched by bilty numbers.");
            }

            $this->log($trip, $userId, 'created_from_lrs', 'Trip created from '.count($invoiceIds).' LR receipt(s).');

            return $trip->refresh()->load(['receipts', 'events', 'expenses', 'status']);
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(int $companyId, int $userId, Trip $trip, array $attributes): Trip
    {
        return DB::transaction(function () use ($userId, $trip, $attributes): Trip {
            $before = $trip->only(['lorry_no', 'owner_party_id', 'driver_party_id', 'broker_party_id']);

            $trip->fill(array_filter([
                'from_city' => $attributes['from_city'] ?? null,
                'to_city' => $attributes['to_city'] ?? null,
                'goods' => $attributes['goods'] ?? null,
                'weight' => $attributes['weight'] ?? null,
                'e_way_bill' => $attributes['e_way_bill'] ?? null,
                'pickup_date' => $attributes['pickup_date'] ?? null,
                'delivered_date' => $attributes['delivered_date'] ?? null,
                'lorry_no' => $attributes['lorry_no'] ?? null,
                'owner_party_id' => $attributes['owner_party_id'] ?? null,
                'driver_party_id' => $attributes['driver_party_id'] ?? null,
                'broker_party_id' => $attributes['broker_party_id'] ?? null,
                'notes' => $attributes['notes'] ?? null,
            ], fn ($value) => $value !== null || array_key_exists('notes', $attributes)));

            $trip->save();

            $this->logAssignmentChanges($trip, $userId, $before);

            return $trip->refresh()->load(['receipts', 'events', 'expenses', 'status']);
        });
    }

    public function changeStatus(int $companyId, int $userId, Trip $trip, int $statusId): Trip
    {
        $status = $this->statuses->findForCompany($companyId, $statusId);

        return DB::transaction(function () use ($userId, $trip, $status): Trip {
            $from = $trip->status?->name;

            $trip->status_id = $status->id;
            $trip->save();

            $this->log($trip, $userId, 'status_changed', "Status: {$from} → {$status->name}.");

            return $trip->refresh()->load(['receipts', 'events', 'expenses', 'status']);
        });
    }

    /**
     * A board drag: the status and the position within the column, in one go.
     */
    public function move(int $companyId, int $userId, Trip $trip, int $statusId, ?float $position): Trip
    {
        $status = $this->statuses->findForCompany($companyId, $statusId);

        return DB::transaction(function () use ($companyId, $userId, $trip, $status, $position): Trip {
            $from = $trip->status?->name;

            $trip->status_id = $status->id;
            $trip->board_position = $position ?? $this->endOfColumn($companyId, $status->id);
            $trip->save();

            if ($from !== $status->name) {
                $this->log($trip, $userId, 'status_changed', "Status: {$from} → {$status->name}.");
            }

            return $trip->refresh()->load(['receipts', 'events', 'expenses', 'status']);
        });
    }

    public function cancel(int $companyId, int $userId, Trip $trip): Trip
    {
        return DB::transaction(function () use ($companyId, $userId, $trip): Trip {
            $cancelled = $this->statuses->findByCode($companyId, 'cancelled');

            $trip->cancelled_at = now();
            $trip->save();

            if ($cancelled !== null && $trip->status_id !== $cancelled->id) {
                $trip = $this->changeStatus($companyId, $userId, $trip, $cancelled->id);
            }

            $this->log($trip, $userId, 'cancelled', 'Trip cancelled.');

            return $trip->refresh()->load(['receipts', 'events', 'expenses', 'status']);
        });
    }

    public function delete(Trip $trip): void
    {
        DB::transaction(function () use ($trip): void {
            TripReceipt::query()->where('trip_id', $trip->id)->delete();
            TripEvent::query()->where('trip_id', $trip->id)->delete();
            TripExpense::query()->where('trip_id', $trip->id)->delete();
            $trip->delete();
        });
    }

    /**
     * Link an LR or Lorry receipt invoice to a trip. The invoice must exist,
     * belong to the company, carry the right template and not be linked yet.
     */
    public function linkReceipt(int $companyId, int $userId, Trip $trip, int $invoiceId, string $type): TripReceipt
    {
        $template = $type === TripReceipt::TYPE_LORRY ? 'lorry_receipt' : 'lr_receipt';

        $invoice = Invoice::query()
            ->where('company_id', $companyId)
            ->where('template_name', $template)
            ->with('customer')
            ->find($invoiceId);

        if ($invoice === null) {
            throw ValidationException::withMessages(['invoice_id' => 'That receipt was not found.']);
        }

        return DB::transaction(function () use ($userId, $trip, $invoice, $type): TripReceipt {
            $row = $this->linkReceiptRow($trip, $invoice, $type);

            $label = $type === TripReceipt::TYPE_LORRY ? 'Lorry Receipt' : 'LR Receipt';
            $this->log($trip, $userId, 'receipt_linked', "{$label} {$invoice->invoice_number} linked.");

            return $row;
        });
    }

    public function unlinkReceipt(int $companyId, int $userId, Trip $trip, int $receiptId): void
    {
        DB::transaction(function () use ($userId, $trip, $receiptId): void {
            $receipt = $trip->receipts->firstWhere('id', $receiptId);

            if ($receipt === null) {
                return;
            }

            if ($receipt->billed_invoice_id !== null) {
                throw ValidationException::withMessages(['receipt_id' => 'An invoiced receipt cannot be unlinked.']);
            }

            $receipt->delete();

            $label = $receipt->type === TripReceipt::TYPE_LORRY ? 'Lorry Receipt' : 'LR Receipt';
            $this->log($trip, $userId, 'receipt_unlinked', "{$label} {$receipt->invoice_number} unlinked.");
        });
    }

    /**
     * Find and link the Invoice Receipt (office invoice) whose consignment
     * line items reference one of this trip's LR numbers. The LR number is
     * stored on the invoice item's `name` field (e.g. "LR-000001") when the
     * office creates the invoice receipt from the LR receipts.
     */
    public function fetchInvoiceReceipts(int $companyId, int $userId, Trip $trip): Trip
    {
        $lrNumbers = $trip->receipts
            ->where('type', TripReceipt::TYPE_LR)
            ->map(fn ($receipt): string => (string) $receipt->invoice_number)
            ->filter()
            ->all();

        if ($lrNumbers === []) {
            throw ValidationException::withMessages(['lr' => 'Link at least one LR receipt first.']);
        }

        $linked = TripReceipt::query()
            ->where('company_id', $companyId)
            ->pluck('invoice_id');

        // The invoice receipt stores each LR as a line item whose `name` is
        // the LR number. Match any invoice whose items name one of our LRs.
        $candidates = Invoice::query()
            ->where('company_id', $companyId)
            ->where('template_name', 'invoice_receipt')
            ->when($linked->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $linked))
            ->whereHas('items', fn ($q) => $q->whereIn('name', $lrNumbers))
            ->with('customer')
            ->latest()
            ->get();

        if ($candidates->isEmpty()) {
            throw ValidationException::withMessages(['invoice' => 'No invoice receipt found for these LR numbers.']);
        }

        return DB::transaction(function () use ($userId, $trip, $candidates): Trip {
            foreach ($candidates as $invoice) {
                $this->linkReceiptRow($trip, $invoice, TripReceipt::TYPE_INVOICE);
                $this->log($trip, $userId, 'receipt_linked', "Invoice Receipt {$invoice->invoice_number} linked.");
            }

            return $trip->refresh()->load(['receipts', 'events', 'expenses', 'status']);
        });
    }

    /**
     * Upload a document to the trip's media collection. Each category is a
     * single-file slot: uploading to a category that already has a file
     * replaces it.
     */
    public function addPod(int $userId, Trip $trip, UploadedFile $file, string $category): Trip
    {
        $existing = $trip->getMedia('pod')->firstWhere('custom_properties.category', $category);

        if ($existing !== null) {
            $existing->delete();
        }

        $trip->addMedia($file)
            ->withCustomProperties(['category' => $category])
            ->toMediaCollection('pod');

        $this->log($trip, $userId, 'pod_added', "Document uploaded ({$category}).");

        return $trip->refresh()->load(['receipts', 'events', 'expenses', 'status']);
    }

    /**
     * Remove a POD document from the trip's media collection.
     */
    public function removePod(int $userId, Trip $trip, int $mediaId): Trip
    {
        $media = $trip->getMedia('pod')->firstWhere('id', $mediaId);

        if ($media !== null) {
            $media->delete();
            $this->log($trip, $userId, 'pod_removed', 'POD document removed.');
        }

        return $trip->refresh()->load(['receipts', 'events', 'expenses', 'status']);
    }

    /** @param array<string, mixed> $attributes */
    public function addExpense(int $userId, Trip $trip, array $attributes): TripExpense
    {
        return DB::transaction(function () use ($userId, $trip, $attributes): TripExpense {
            $expense = $trip->expenses()->create([
                'company_id' => $trip->company_id,
                'category' => $attributes['category'],
                'amount' => (int) $attributes['amount'],
                'note' => $attributes['note'] ?? null,
                'expense_date' => $attributes['expense_date'],
                'user_id' => $userId,
            ]);

            $this->log($trip, $userId, 'expense_added', 'Expense added: '.ucfirst($expense->category).'.');

            return $expense;
        });
    }

    public function removeExpense(int $userId, Trip $trip, int $expenseId): void
    {
        DB::transaction(function () use ($userId, $trip, $expenseId): void {
            $expense = $trip->expenses->firstWhere('id', $expenseId);

            if ($expense === null) {
                return;
            }

            $expense->delete();
            $this->log($trip, $userId, 'expense_removed', 'Expense removed: '.ucfirst($expense->category).'.');
        });
    }

    /**
     * The money of one trip, in integer minor units.
     *
     * The lorry hire's cost, the owner paid and the balance come from the bill
     * behind the linked lorry receipt (the Lorry Receipt module mirrors each
     * receipt into a host Bill with SupplierPayments allocated to it), so the
     * bill is the single source of truth. Without a bill the linked receipt's
     * denormalized amount is the cost and nothing is paid.
     *
     * @return array{revenue: int, cost: int, owner_paid: int, owner_balance: int, profit: int, bill_id: int|null, bill_number: string|null, bill_status: string|null, receivable: list<array<string, mixed>>, payable: list<array<string, mixed>>}
     */
    public function money(Trip $trip): array
    {
        // Revenue: prefer the Invoice Receipt (the final bill to the customer
        // after delivery). Fall back to LR receipts when no invoice receipt is
        // linked yet — the LR is the estimate, the invoice is the actual.
        $invoiceReceipts = $trip->receipts->where('type', TripReceipt::TYPE_INVOICE);
        $revenue = $invoiceReceipts->isNotEmpty()
            ? (int) $invoiceReceipts->sum('amount')
            : (int) $trip->receipts->where('type', TripReceipt::TYPE_LR)->sum('amount');
        $expenses = (int) $trip->expenses->sum('amount');

        $bills = $this->lorryBills($trip);

        if ($bills->isNotEmpty()) {
            $lorryCost = (int) $bills->sum('total');
            $ownerPaid = $lorryCost - (int) $bills->sum('due_amount');
            $ownerBalance = (int) $bills->sum('due_amount');
            $bill = $bills->first();
        } else {
            $lorryCost = (int) $trip->receipts->where('type', TripReceipt::TYPE_LORRY)->sum('amount');
            $ownerPaid = 0;
            $ownerBalance = $lorryCost;
            $bill = null;
        }

        $cost = $lorryCost + $expenses;

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'owner_paid' => $ownerPaid,
            'owner_balance' => $ownerBalance,
            'profit' => $revenue - $cost,
            'bill_id' => $bill?->id,
            'bill_number' => $bill?->number,
            'bill_status' => $bill?->settlement_status,
            'receivable' => $this->receivable($trip),
            'payable' => $this->payable($trip),
        ];
    }

    /**
     * The bills behind the trip's linked lorry receipts, if the Lorry Receipt
     * module mirrored them. One query, whatever the trip count.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Bill>
     */
    private function lorryBills(Trip $trip)
    {
        $invoiceIds = $trip->receipts
            ->where('type', TripReceipt::TYPE_LORRY)
            ->pluck('invoice_id');

        if ($invoiceIds->isEmpty()) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return Bill::query()
            ->forCompany((int) $trip->company_id)
            ->whereIn('id', Invoice::query()
                ->whereIn('id', $invoiceIds)
                ->select('tr_bill_id'))
            ->get();
    }

    /**
     * The customer-side money: invoice receipts linked to this trip, their
     * totals, due amounts, and the customer payments allocated to them.
     *
     * @return list<array<string, mixed>>
     */
    private function receivable(Trip $trip): array
    {
        $invoiceIds = $trip->receipts
            ->where('type', TripReceipt::TYPE_INVOICE)
            ->pluck('invoice_id');

        if ($invoiceIds->isEmpty()) {
            return [];
        }

        $invoices = Invoice::query()
            ->where('company_id', (int) $trip->company_id)
            ->whereIn('id', $invoiceIds)
            ->with(['customer', 'payments.paymentMethod'])
            ->get();

        return $invoices->map(function (Invoice $invoice): array {
            return [
                'invoice_id' => (int) $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer?->name,
                'total' => (int) $invoice->total,
                'due_amount' => (int) $invoice->due_amount,
                'paid_status' => $invoice->paid_status,
                'payments' => $invoice->payments
                    ->sortBy('payment_date')
                    ->map(fn ($payment): array => [
                        'id' => (int) $payment->id,
                        'number' => $payment->payment_number,
                        'date' => $this->safeDate($payment->payment_date),
                        'amount' => (int) $payment->pivot->amount,
                        'notes' => $payment->notes,
                        'method' => $payment->paymentMethod?->name,
                    ])
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    }

    /**
     * The owner-side money: bills and supplier payments behind the trip's
     * linked lorry receipts.
     *
     * @return list<array<string, mixed>>
     */
    private function payable(Trip $trip): array
    {
        $invoiceIds = $trip->receipts
            ->where('type', TripReceipt::TYPE_LORRY)
            ->pluck('invoice_id');

        if ($invoiceIds->isEmpty()) {
            return [];
        }

        $invoices = Invoice::query()
            ->where('company_id', (int) $trip->company_id)
            ->whereIn('id', $invoiceIds)
            ->get(['id', 'invoice_number', 'tr_bill_id', 'tr_advance_payment_id', 'tr_final_payment_id']);

        $billIds = $invoices->pluck('tr_bill_id')->filter()->unique()->values();
        $paymentIds = $invoices->pluck('tr_advance_payment_id')
            ->merge($invoices->pluck('tr_final_payment_id'))
            ->filter()
            ->unique()
            ->values();

        $bills = $billIds->isNotEmpty()
            ? Bill::query()->forCompany((int) $trip->company_id)->whereIn('id', $billIds)->with('supplier')->get()
            : collect();

        $supplierPayments = $paymentIds->isNotEmpty()
            ? SupplierPayment::query()->forCompany((int) $trip->company_id)->whereIn('id', $paymentIds)->with('paymentMethod')->get()
            : collect();

        return $invoices->map(function (Invoice $invoice) use ($bills, $supplierPayments): array {
            $bill = $bills->firstWhere('id', $invoice->tr_bill_id);

            $payments = collect();

            $advance = $supplierPayments->firstWhere('id', $invoice->tr_advance_payment_id);
            if ($advance) {
                $payments->push([
                    'id' => (int) $advance->id,
                    'type' => 'advance',
                    'number' => $advance->number,
                    'date' => $this->safeDate($advance->payment_date),
                    'amount' => (int) $advance->amount,
                    'reference' => $advance->reference,
                    'notes' => $advance->notes,
                    'method' => $advance->paymentMethod?->name,
                ]);
            }

            $final = $supplierPayments->firstWhere('id', $invoice->tr_final_payment_id);
            if ($final) {
                $payments->push([
                    'id' => (int) $final->id,
                    'type' => 'final',
                    'number' => $final->number,
                    'date' => $this->safeDate($final->payment_date),
                    'amount' => (int) $final->amount,
                    'reference' => $final->reference,
                    'notes' => $final->notes,
                    'method' => $final->paymentMethod?->name,
                ]);
            }

            return [
                'invoice_number' => $invoice->invoice_number,
                'bill_id' => $bill?->id,
                'bill_number' => $bill?->number,
                'bill_reference' => $bill?->reference,
                'supplier_name' => $bill?->supplier?->name,
                'bill_total' => $bill ? (int) $bill->total : null,
                'bill_due' => $bill ? (int) $bill->due_amount : null,
                'bill_status' => $bill?->settlement_status,
                'bill_due_date' => $this->safeDate($bill?->due_date),
                'payments' => $payments->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * Format a date-ish value as Y-m-d, tolerating strings, Carbon, null.
     */
    private function safeDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $text = trim((string) $value);

        return $text === '' ? null : substr($text, 0, 10);
    }

    /**
     * LR receipts of the company that are not on any trip: the map picker's
     * list. Optionally narrowed to one customer.
     *
     * @return Collection<int, Invoice>
     */
    public function unlinkedLrInvoices(int $companyId, ?int $customerId = null): Collection
    {
        $linked = TripReceipt::query()
            ->where('company_id', $companyId)
            ->pluck('invoice_id');

        return Invoice::query()
            ->where('company_id', $companyId)
            ->where('template_name', 'lr_receipt')
            ->when($customerId !== null, fn ($q) => $q->where('customer_id', $customerId))
            ->when($linked->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $linked))
            ->with('customer')
            ->latest()
            ->limit(100)
            ->get();
    }

    /**
     * Lorry receipts of the company that are not on any trip.
     *
     * @return Collection<int, Invoice>
     */
    public function unlinkedLorryInvoices(int $companyId): Collection
    {
        $linked = TripReceipt::query()
            ->where('company_id', $companyId)
            ->pluck('invoice_id');

        return Invoice::query()
            ->where('company_id', $companyId)
            ->where('template_name', 'lorry_receipt')
            ->when($linked->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $linked))
            ->with('customer')
            ->latest()
            ->limit(100)
            ->get();
    }

    /**
     * Auto-match lorry receipts to LR numbers: a lorry receipt whose
     * "received bilties" field lists one of the given LR numbers carries
     * that load. Matching is token-based so "LR-101, LR-102" matches
     * "LR-101" but not "LR-1011".
     *
     * @param  array<int, string>  $lrNumbers
     * @return Collection<int, Invoice>
     */
    public function matchLorryReceipts(int $companyId, array $lrNumbers): Collection
    {
        $lrNumbers = array_values(array_filter(array_map('trim', $lrNumbers)));

        if ($lrNumbers === []) {
            return collect();
        }

        $linked = TripReceipt::query()
            ->where('company_id', $companyId)
            ->pluck('invoice_id');

        return Invoice::query()
            ->where('company_id', $companyId)
            ->where('template_name', 'lorry_receipt')
            ->whereNotNull('tr_received_no_bilties')
            ->where('tr_received_no_bilties', '!=', '')
            ->when($linked->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $linked))
            ->with('customer')
            ->latest()
            ->limit(100)
            ->get()
            ->filter(function (Invoice $invoice) use ($lrNumbers): bool {
                $tokens = preg_split('/[\s,;]+/u', (string) $invoice->tr_received_no_bilties) ?: [];

                return collect($tokens)
                    ->map(fn (string $token): string => trim($token))
                    ->intersect($lrNumbers)
                    ->isNotEmpty();
            })
            ->values();
    }

    /**
     * The amount a linked receipt counts for, in minor units: the invoice's
     * own total when it has one, otherwise the tr_ charge fields (major
     * units) converted.
     */
    public function receiptAmount(Invoice $invoice, string $type): int
    {
        if ((int) $invoice->total > 0) {
            return (int) $invoice->total;
        }

        if ($type === TripReceipt::TYPE_LORRY) {
            $major = (int) ($invoice->tr_lorry_hire_amount ?? 0) + (int) ($invoice->tr_other_charges_amount ?? 0);

            return $major * 100;
        }

        $major = (int) ($invoice->tr_net_amount ?? 0);

        return $major * 100;
    }

    private function linkReceiptRow(Trip $trip, Invoice $invoice, string $type): TripReceipt
    {
        return TripReceipt::query()->create([
            'company_id' => $trip->company_id,
            'trip_id' => $trip->id,
            'invoice_id' => $invoice->id,
            'type' => $type,
            'amount' => $this->receiptAmount($invoice, $type),
            'invoice_number' => $invoice->invoice_number,
            'customer_id' => $invoice->customer_id,
            'customer_name' => $invoice->customer?->name,
            'template_name' => $invoice->template_name,
        ]);
    }

    private function endOfColumn(int $companyId, int $statusId): float
    {
        $last = Trip::query()
            ->where('company_id', $companyId)
            ->where('status_id', $statusId)
            ->lockForUpdate()
            ->max('board_position');

        return (float) $last + 1;
    }

    private function log(Trip $trip, int $userId, string $type, string $message): void
    {
        TripEvent::query()->create([
            'company_id' => $trip->company_id,
            'trip_id' => $trip->id,
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'created_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $before */
    private function logAssignmentChanges(Trip $trip, int $userId, array $before): void
    {
        if ((string) ($before['lorry_no'] ?? '') !== (string) ($trip->lorry_no ?? '')) {
            $this->log($trip, $userId, 'lorry_changed', 'Lorry changed to '.($trip->lorry_no ?? '—').'.');
        }

        if ((int) ($before['owner_party_id'] ?? 0) !== (int) ($trip->owner_party_id ?? 0)) {
            $this->log($trip, $userId, 'owner_changed', 'Lorry owner '.($trip->owner_party_id ? 'assigned' : 'removed').'.');
        }
    }
}
