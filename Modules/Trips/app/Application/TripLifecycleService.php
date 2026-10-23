<?php

declare(strict_types=1);

namespace Modules\Trips\Application;

use App\Domains\Receivables\Models\Payment;
use App\Domains\Receivables\Models\PaymentAllocation;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripEvent;
use Modules\Trips\Models\TripExpense;
use Modules\Trips\Models\TripReceipt;

/**
 * Automates trip lifecycle transitions and linkages as transport documents
 * (LR Receipts, Lorry Receipts, Invoice Receipts, Customer Payments) are recorded.
 */
class TripLifecycleService
{
    private static bool $isSyncing = false;

    private const STATUS_RANKS = [
        'booked' => 1,
        'assigned' => 2,
        'in_transit' => 3,
        'delivered' => 4,
    ];

    public function __construct(
        private readonly TripStatusService $statuses,
        private readonly TripNumberSequence $sequence,
        private readonly TripService $tripService,
    ) {}

    /**
     * Dispatch an invoice change to the appropriate handler based on template_name.
     */
    public function syncFromInvoice(Invoice $invoice): void
    {
        if (self::$isSyncing) {
            return;
        }

        if (! $invoice->company_id || ! $invoice->template_name) {
            return;
        }

        self::$isSyncing = true;

        try {
            match ($invoice->template_name) {
                'lr_receipt' => $this->handleLrReceipt($invoice),
                'lorry_receipt' => $this->handleLorryReceipt($invoice),
                'invoice_receipt' => $this->handleInvoiceReceipt($invoice),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::warning("TripLifecycleService::syncFromInvoice failed for invoice {$invoice->id}: {$e->getMessage()}", [
                'exception' => $e,
            ]);
        } finally {
            self::$isSyncing = false;
        }
    }

    /**
     * When an invoice item with a consignment number is saved, sync its parent invoice receipt.
     */
    public function syncFromInvoiceItem(InvoiceItem $item): void
    {
        if (self::$isSyncing) {
            return;
        }

        if (! $item->invoice_id) {
            return;
        }

        $invoice = $item->invoice ?? Invoice::query()->find($item->invoice_id);
        if ($invoice && $invoice->template_name === 'invoice_receipt') {
            $this->syncFromInvoice($invoice);
        }
    }

    /**
     * When a customer payment allocation is recorded, link or log on the trip.
     */
    public function syncFromPaymentAllocation(PaymentAllocation $allocation): void
    {
        if (self::$isSyncing) {
            return;
        }

        if (! $allocation->invoice_id) {
            return;
        }

        self::$isSyncing = true;

        try {
            $invoice = $allocation->invoice ?? Invoice::query()->find($allocation->invoice_id);
            if ($invoice) {
                $payment = $allocation->payment ?? Payment::query()->find($allocation->payment_id);
                $this->handleInvoicePayment($invoice, $payment, (int) $allocation->amount);
            }
        } catch (\Throwable $e) {
            Log::warning("TripLifecycleService::syncFromPaymentAllocation failed: {$e->getMessage()}", [
                'exception' => $e,
            ]);
        } finally {
            self::$isSyncing = false;
        }
    }

    /**
     * When a payment is recorded or updated, sync all its allocated invoices.
     */
    public function syncFromPayment(Payment $payment): void
    {
        if (self::$isSyncing) {
            return;
        }

        self::$isSyncing = true;

        try {
            $payment->loadMissing(['allocations.invoice']);
            foreach ($payment->allocations as $allocation) {
                if ($allocation->invoice) {
                    $this->handleInvoicePayment($allocation->invoice, $payment, (int) $allocation->amount);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("TripLifecycleService::syncFromPayment failed: {$e->getMessage()}", [
                'exception' => $e,
            ]);
        } finally {
            self::$isSyncing = false;
        }
    }

    /**
     * When an invoice is deleted, remove any trip receipt linking it.
     */
    public function handleInvoiceDeleted(Invoice $invoice): void
    {
        TripReceipt::query()
            ->where('company_id', $invoice->company_id)
            ->where('invoice_id', $invoice->id)
            ->delete();
    }

    /**
     * 1. LR Receipt:
     * Auto-create a Trip with trip_no matching the LR number (or attach to an existing trip),
     * set status to 'Booked', and link the LR receipt.
     */
    public function handleLrReceipt(Invoice $invoice): ?Trip
    {
        return DB::transaction(function () use ($invoice): ?Trip {
            $companyId = (int) $invoice->company_id;
            $this->statuses->ensureDefaults($companyId);

            $rawLr = trim((string) $invoice->invoice_number);
            $numericLr = (int) preg_replace('/\D/', '', $rawLr);

            // Check if already linked to a trip
            $existingReceipt = TripReceipt::query()
                ->where('company_id', $companyId)
                ->where('invoice_id', $invoice->id)
                ->first();

            if ($existingReceipt) {
                $trip = Trip::query()->find($existingReceipt->trip_id);
                if ($trip) {
                    $existingReceipt->update([
                        'amount' => $this->tripService->receiptAmount($invoice, TripReceipt::TYPE_LR),
                        'customer_id' => $invoice->customer_id,
                        'customer_name' => $invoice->customer?->name,
                        'invoice_number' => $invoice->invoice_number,
                    ]);

                    $this->syncLrFieldsToTrip($trip, $invoice);
                    $this->matchAndLinkLorryReceiptsForTrip($trip);
                    $this->matchAndLinkInvoiceReceiptsForTrip($trip);

                    return $trip;
                }
            }

            // Check if this LR is already referenced by an existing Lorry Receipt that has a Trip
            $trip = $this->findTripByReferencedLorryReceipt($companyId, $rawLr);

            // Check if this LR is referenced by an existing Invoice Receipt that has a Trip
            if (! $trip) {
                $trip = $this->findTripByReferencedInvoiceReceipt($companyId, $rawLr);
            }

            // Check if a trip already exists for this trip_no without an LR receipt
            if (! $trip && $numericLr > 0) {
                $candidate = Trip::query()
                    ->where('company_id', $companyId)
                    ->where('trip_no', $numericLr)
                    ->first();

                if ($candidate && ! $candidate->receipts()->where('type', TripReceipt::TYPE_LR)->exists()) {
                    $trip = $candidate;
                }
            }

            // Create new trip if not found
            if (! $trip) {
                $tripNo = ($numericLr > 0 && ! Trip::query()->where('company_id', $companyId)->where('trip_no', $numericLr)->exists())
                    ? $numericLr
                    : $this->sequence->next($companyId);

                $bookedStatus = $this->statuses->findByCode($companyId, 'booked')
                    ?? $this->statuses->defaultFor($companyId);

                $trip = Trip::query()->create([
                    'company_id' => $companyId,
                    'trip_no' => $tripNo,
                    'status_id' => $bookedStatus->id,
                    'board_position' => $this->endOfColumn($companyId, $bookedStatus->id),
                    'from_city' => $invoice->tr_from_name,
                    'to_city' => $invoice->tr_to_name,
                    'goods' => $invoice->tr_description_goods,
                    'weight' => $invoice->tr_charged_weight ?? $invoice->tr_weight ?? $invoice->tr_pkg_weight,
                    'pickup_date' => $invoice->invoice_date,
                    'lorry_no' => $invoice->tr_truck_no ?? $invoice->tr_lorry_no,
                ]);

                $this->logEvent($trip, $invoice->creator_id, 'created', "Trip #{$trip->trip_no} created for LR Receipt {$invoice->invoice_number}. Status: Booked.");
            } else {
                $this->syncLrFieldsToTrip($trip, $invoice);
            }

            // Link LR receipt
            TripReceipt::query()->updateOrCreate(
                ['company_id' => $companyId, 'invoice_id' => $invoice->id],
                [
                    'trip_id' => $trip->id,
                    'type' => TripReceipt::TYPE_LR,
                    'amount' => $this->tripService->receiptAmount($invoice, TripReceipt::TYPE_LR),
                    'invoice_number' => $invoice->invoice_number,
                    'customer_id' => $invoice->customer_id,
                    'customer_name' => $invoice->customer?->name,
                    'template_name' => $invoice->template_name,
                ]
            );

            // Auto-link any matching Lorry Receipts or Invoice Receipts
            $this->matchAndLinkLorryReceiptsForTrip($trip);
            $this->matchAndLinkInvoiceReceiptsForTrip($trip);

            return $trip;
        });
    }

    /**
     * 2. Lorry Receipt:
     * Parses tr_received_no_bilties (LR numbers). Merges trips if multiple LRs exist.
     * Links the Lorry Receipt, copies vehicle/driver/owner, and advances status to 'In Transit'.
     */
    public function handleLorryReceipt(Invoice $invoice): ?Trip
    {
        return DB::transaction(function () use ($invoice): ?Trip {
            $companyId = (int) $invoice->company_id;
            $this->statuses->ensureDefaults($companyId);

            $tokens = $this->parseTokens((string) $invoice->tr_received_no_bilties);

            // Pre-seed any missing LR trips for these tokens if the LR invoices exist
            foreach ($tokens as $token) {
                $lrInvoice = Invoice::query()
                    ->where('company_id', $companyId)
                    ->where('template_name', 'lr_receipt')
                    ->where('invoice_number', $token)
                    ->first();

                if ($lrInvoice) {
                    $this->handleLrReceipt($lrInvoice);
                }
            }

            // Find all trips matching these LR tokens
            $matchedTrips = $this->findTripsForLrTokens($companyId, $tokens);

            if ($matchedTrips->isEmpty()) {
                // If this lorry receipt is already linked to a trip, reuse it
                $existingReceipt = TripReceipt::query()
                    ->where('company_id', $companyId)
                    ->where('invoice_id', $invoice->id)
                    ->first();

                if ($existingReceipt) {
                    $trip = Trip::query()->find($existingReceipt->trip_id);
                    if ($trip) {
                        $this->syncLorryFieldsToTrip($trip, $invoice);
                        $this->advanceStatus($trip, 'in_transit');

                        return $trip;
                    }
                }

                // Standalone Lorry Receipt trip
                $inTransitStatus = $this->statuses->findByCode($companyId, 'in_transit')
                    ?? $this->statuses->defaultFor($companyId);

                $trip = Trip::query()->create([
                    'company_id' => $companyId,
                    'trip_no' => $this->sequence->next($companyId),
                    'status_id' => $inTransitStatus->id,
                    'board_position' => $this->endOfColumn($companyId, $inTransitStatus->id),
                    'lorry_no' => $invoice->tr_lorry_no,
                    'owner_party_id' => $invoice->tr_owner_party_id,
                    'driver_party_id' => $invoice->tr_driver_party_id,
                    'broker_party_id' => $invoice->tr_broker_party_id,
                    'pickup_date' => $invoice->invoice_date,
                    'notes' => "Created from Lorry Receipt {$invoice->invoice_number}",
                ]);

                TripReceipt::query()->updateOrCreate(
                    ['company_id' => $companyId, 'invoice_id' => $invoice->id],
                    [
                        'trip_id' => $trip->id,
                        'type' => TripReceipt::TYPE_LORRY,
                        'amount' => $this->tripService->receiptAmount($invoice, TripReceipt::TYPE_LORRY),
                        'invoice_number' => $invoice->invoice_number,
                        'customer_id' => $invoice->customer_id,
                        'customer_name' => $invoice->customer?->name,
                        'template_name' => $invoice->template_name,
                    ]
                );

                $this->logEvent($trip, $invoice->creator_id, 'created', "Trip #{$trip->trip_no} created for Lorry Receipt {$invoice->invoice_number}. Status: In Transit.");

                return $trip;
            }

            // If multiple trips matched, MERGE them into the master trip
            $masterTrip = $matchedTrips->first();
            if ($matchedTrips->count() > 1) {
                $masterTrip = $this->mergeTrips($masterTrip, $matchedTrips->slice(1));
            }

            // Link Lorry Receipt to master trip
            TripReceipt::query()->updateOrCreate(
                ['company_id' => $companyId, 'invoice_id' => $invoice->id],
                [
                    'trip_id' => $masterTrip->id,
                    'type' => TripReceipt::TYPE_LORRY,
                    'amount' => $this->tripService->receiptAmount($invoice, TripReceipt::TYPE_LORRY),
                    'invoice_number' => $invoice->invoice_number,
                    'customer_id' => $invoice->customer_id,
                    'customer_name' => $invoice->customer?->name,
                    'template_name' => $invoice->template_name,
                ]
            );

            $this->syncLorryFieldsToTrip($masterTrip, $invoice);
            $this->advanceStatus($masterTrip, 'in_transit');
            $this->logEvent($masterTrip, $invoice->creator_id, 'receipt_linked', "Lorry Receipt {$invoice->invoice_number} linked. Status: In Transit.");

            return $masterTrip;
        });
    }

    /**
     * 3. Invoice Receipt:
     * Matches consignment line items (LR numbers). Merges trips if multiple LRs.
     * Links the Invoice Receipt (Revenue) and advances status to 'Delivered'.
     */
    public function handleInvoiceReceipt(Invoice $invoice): ?Trip
    {
        return DB::transaction(function () use ($invoice): ?Trip {
            $companyId = (int) $invoice->company_id;
            $this->statuses->ensureDefaults($companyId);

            $consignmentNumbers = $this->extractConsignmentNumbers($invoice);

            if (empty($consignmentNumbers)) {
                return null;
            }

            // Pre-seed any missing LR trips for these consignment numbers
            foreach ($consignmentNumbers as $num) {
                $lrInvoice = Invoice::query()
                    ->where('company_id', $companyId)
                    ->where('template_name', 'lr_receipt')
                    ->where('invoice_number', $num)
                    ->first();

                if ($lrInvoice) {
                    $this->handleLrReceipt($lrInvoice);
                }
            }

            // Find all trips matching these LR / consignment numbers
            $matchedTrips = $this->findTripsForLrTokens($companyId, $consignmentNumbers);

            if ($matchedTrips->isEmpty()) {
                return null;
            }

            // Merge if multiple trips represent consignments on this single invoice receipt
            $masterTrip = $matchedTrips->first();
            if ($matchedTrips->count() > 1) {
                $masterTrip = $this->mergeTrips($masterTrip, $matchedTrips->slice(1));
            }

            // Link Invoice Receipt
            TripReceipt::query()->updateOrCreate(
                ['company_id' => $companyId, 'invoice_id' => $invoice->id],
                [
                    'trip_id' => $masterTrip->id,
                    'type' => TripReceipt::TYPE_INVOICE,
                    'amount' => $this->tripService->receiptAmount($invoice, TripReceipt::TYPE_INVOICE),
                    'invoice_number' => $invoice->invoice_number,
                    'customer_id' => $invoice->customer_id,
                    'customer_name' => $invoice->customer?->name,
                    'template_name' => $invoice->template_name,
                ]
            );

            // Delivered date
            if ($invoice->invoice_date && ! $masterTrip->delivered_date) {
                $masterTrip->update(['delivered_date' => $invoice->invoice_date]);
            }

            $this->advanceStatus($masterTrip, 'delivered');
            $this->logEvent(
                $masterTrip,
                $invoice->creator_id,
                'receipt_linked',
                "Invoice Receipt {$invoice->invoice_number} linked for consignment(s) [".implode(', ', $consignmentNumbers).']. Status: Delivered.'
            );

            return $masterTrip;
        });
    }

    /**
     * 4. Customer Payment:
     * Reflects payment on linked trips and logs a payment event.
     */
    public function handleInvoicePayment(Invoice $invoice, ?Payment $payment, int $amount): void
    {
        $companyId = (int) $invoice->company_id;

        // Find trips that link this invoice
        $tripReceipts = TripReceipt::query()
            ->where('company_id', $companyId)
            ->where('invoice_id', $invoice->id)
            ->get();

        // If not linked yet but it's an invoice receipt, link it now
        if ($tripReceipts->isEmpty() && $invoice->template_name === 'invoice_receipt') {
            $trip = $this->handleInvoiceReceipt($invoice);
            if ($trip) {
                $tripReceipts = TripReceipt::query()
                    ->where('company_id', $companyId)
                    ->where('invoice_id', $invoice->id)
                    ->get();
            }
        }

        foreach ($tripReceipts as $receipt) {
            $trip = Trip::query()->find($receipt->trip_id);
            if (! $trip) {
                continue;
            }

            $payNumber = $payment?->payment_number ?? 'Payment';
            $formattedAmount = number_format($amount / 100, 2);

            $alreadyLogged = TripEvent::query()
                ->where('trip_id', $trip->id)
                ->where('type', 'payment_received')
                ->where('message', 'like', "%{$payNumber}%")
                ->exists();

            if (! $alreadyLogged) {
                $this->logEvent(
                    $trip,
                    $payment?->creator_id,
                    'payment_received',
                    "Payment {$payNumber} received for Invoice Receipt {$invoice->invoice_number} (₹ {$formattedAmount})."
                );
            }
        }
    }

    /**
     * Merges multiple trips into a single master trip.
     * All receipts, events, expenses, and PODs are reparented.
     * The other trips are deleted.
     *
     * @param  Collection<int, Trip>  $otherTrips
     */
    public function mergeTrips(Trip $masterTrip, Collection $otherTrips): Trip
    {
        foreach ($otherTrips as $otherTrip) {
            if ($otherTrip->id === $masterTrip->id) {
                continue;
            }

            // Transfer receipts avoiding duplicates
            $otherReceipts = TripReceipt::query()->where('trip_id', $otherTrip->id)->get();
            foreach ($otherReceipts as $receipt) {
                $duplicate = TripReceipt::query()
                    ->where('trip_id', $masterTrip->id)
                    ->where('invoice_id', $receipt->invoice_id)
                    ->exists();

                if ($duplicate) {
                    $receipt->delete();
                } else {
                    $receipt->update(['trip_id' => $masterTrip->id]);
                }
            }

            // Transfer events
            TripEvent::query()->where('trip_id', $otherTrip->id)->update(['trip_id' => $masterTrip->id]);

            // Transfer expenses
            TripExpense::query()->where('trip_id', $otherTrip->id)->update(['trip_id' => $masterTrip->id]);

            // Transfer media (POD)
            DB::table('media')
                ->where('model_type', 'trip')
                ->where('model_id', $otherTrip->id)
                ->update(['model_id' => $masterTrip->id]);

            $otherTripNo = $otherTrip->trip_no;

            Trip::withoutEvents(function () use ($otherTrip): void {
                $otherTrip->delete();
            });

            $this->logEvent($masterTrip, null, 'trip_merged', "Merged Trip #{$otherTripNo} into this trip.");
        }

        return $masterTrip->refresh();
    }

    /**
     * Advance a trip status forward without downgrading an already advanced trip.
     */
    public function advanceStatus(Trip $trip, string $targetCode): void
    {
        $currentCode = $trip->status?->code ?? 'booked';
        $currentRank = self::STATUS_RANKS[$currentCode] ?? 1;
        $targetRank = self::STATUS_RANKS[$targetCode] ?? 1;

        if ($targetRank > $currentRank) {
            $targetStatus = $this->statuses->findByCode((int) $trip->company_id, $targetCode);
            if ($targetStatus) {
                $trip->status_id = $targetStatus->id;
                $trip->save();
            }
        }
    }

    /**
     * Parse bilty / consignment numbers from free-form text.
     *
     * @return list<string>
     */
    public function parseTokens(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }

    /**
     * Extract consignment numbers from an invoice receipt's line items.
     *
     * @return list<string>
     */
    public function extractConsignmentNumbers(Invoice $invoice): array
    {
        $invoice->loadMissing('items');

        $numbers = collect();

        foreach ($invoice->items as $item) {
            if ($item->tr_consignment_number !== null && trim((string) $item->tr_consignment_number) !== '') {
                $numbers->push(trim((string) $item->tr_consignment_number));
            } elseif ($item->name !== null) {
                $name = trim((string) $item->name);
                if ($name !== '' && ! in_array(strtolower($name), ['consignment', 'item', 'product', 'freight'], true)) {
                    $numbers->push($name);
                }
            }
        }

        return $numbers->unique()->values()->all();
    }

    /**
     * Find all trips that contain any of the given LR tokens (either in receipts or by trip_no).
     *
     * @param  list<string>  $tokens
     * @return Collection<int, Trip>
     */
    private function findTripsForLrTokens(int $companyId, array $tokens): Collection
    {
        if (empty($tokens)) {
            return collect();
        }

        $receiptTripIds = TripReceipt::query()
            ->where('company_id', $companyId)
            ->where('type', TripReceipt::TYPE_LR)
            ->whereIn('invoice_number', $tokens)
            ->pluck('trip_id');

        $numericTokens = array_values(array_filter(array_map('intval', $tokens)));
        $tripNoIds = ! empty($numericTokens)
            ? Trip::query()->where('company_id', $companyId)->whereIn('trip_no', $numericTokens)->pluck('id')
            : collect();

        $allIds = $receiptTripIds->merge($tripNoIds)->unique()->values();

        if ($allIds->isEmpty()) {
            return collect();
        }

        return Trip::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $allIds)
            ->orderBy('id')
            ->get();
    }

    private function findTripByReferencedLorryReceipt(int $companyId, string $lrNumber): ?Trip
    {
        $lorryInvoices = Invoice::query()
            ->where('company_id', $companyId)
            ->where('template_name', 'lorry_receipt')
            ->whereNotNull('tr_received_no_bilties')
            ->where('tr_received_no_bilties', '!=', '')
            ->get();

        foreach ($lorryInvoices as $lorry) {
            $tokens = $this->parseTokens((string) $lorry->tr_received_no_bilties);
            if (in_array($lrNumber, $tokens, true)) {
                $receipt = TripReceipt::query()
                    ->where('company_id', $companyId)
                    ->where('invoice_id', $lorry->id)
                    ->first();

                if ($receipt) {
                    return Trip::query()->find($receipt->trip_id);
                }
            }
        }

        return null;
    }

    private function findTripByReferencedInvoiceReceipt(int $companyId, string $lrNumber): ?Trip
    {
        $invoiceReceipt = Invoice::query()
            ->where('company_id', $companyId)
            ->where('template_name', 'invoice_receipt')
            ->whereHas('items', fn ($q) => $q->where('tr_consignment_number', $lrNumber)->orWhere('name', $lrNumber))
            ->first();

        if ($invoiceReceipt) {
            $receipt = TripReceipt::query()
                ->where('company_id', $companyId)
                ->where('invoice_id', $invoiceReceipt->id)
                ->first();

            if ($receipt) {
                return Trip::query()->find($receipt->trip_id);
            }
        }

        return null;
    }

    private function matchAndLinkLorryReceiptsForTrip(Trip $trip): void
    {
        $lrNumbers = $trip->receipts()
            ->where('type', TripReceipt::TYPE_LR)
            ->pluck('invoice_number')
            ->filter()
            ->values()
            ->all();

        if (empty($lrNumbers)) {
            return;
        }

        $lorryMatches = $this->tripService->matchLorryReceipts((int) $trip->company_id, $lrNumbers);
        foreach ($lorryMatches as $lorry) {
            TripReceipt::query()->updateOrCreate(
                ['company_id' => $trip->company_id, 'invoice_id' => $lorry->id],
                [
                    'trip_id' => $trip->id,
                    'type' => TripReceipt::TYPE_LORRY,
                    'amount' => $this->tripService->receiptAmount($lorry, TripReceipt::TYPE_LORRY),
                    'invoice_number' => $lorry->invoice_number,
                    'customer_id' => $lorry->customer_id,
                    'customer_name' => $lorry->customer?->name,
                    'template_name' => $lorry->template_name,
                ]
            );

            $this->syncLorryFieldsToTrip($trip, $lorry);
            $this->advanceStatus($trip, 'in_transit');
        }
    }

    private function matchAndLinkInvoiceReceiptsForTrip(Trip $trip): void
    {
        $lrNumbers = $trip->receipts()
            ->where('type', TripReceipt::TYPE_LR)
            ->pluck('invoice_number')
            ->filter()
            ->values()
            ->all();

        if (empty($lrNumbers)) {
            return;
        }

        $linked = TripReceipt::query()
            ->where('company_id', $trip->company_id)
            ->pluck('invoice_id');

        $invoices = Invoice::query()
            ->where('company_id', $trip->company_id)
            ->where('template_name', 'invoice_receipt')
            ->when($linked->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $linked))
            ->whereHas('items', function ($q) use ($lrNumbers) {
                $q->whereIn('tr_consignment_number', $lrNumbers)
                    ->orWhereIn('name', $lrNumbers);
            })
            ->get();

        foreach ($invoices as $invoice) {
            TripReceipt::query()->updateOrCreate(
                ['company_id' => $trip->company_id, 'invoice_id' => $invoice->id],
                [
                    'trip_id' => $trip->id,
                    'type' => TripReceipt::TYPE_INVOICE,
                    'amount' => $this->tripService->receiptAmount($invoice, TripReceipt::TYPE_INVOICE),
                    'invoice_number' => $invoice->invoice_number,
                    'customer_id' => $invoice->customer_id,
                    'customer_name' => $invoice->customer?->name,
                    'template_name' => $invoice->template_name,
                ]
            );

            if ($invoice->invoice_date && ! $trip->delivered_date) {
                $trip->update(['delivered_date' => $invoice->invoice_date]);
            }

            $this->advanceStatus($trip, 'delivered');
        }
    }

    private function syncLrFieldsToTrip(Trip $trip, Invoice $invoice): void
    {
        $updates = [];
        if (! $trip->from_city && $invoice->tr_from_name) {
            $updates['from_city'] = $invoice->tr_from_name;
        }
        if (! $trip->to_city && $invoice->tr_to_name) {
            $updates['to_city'] = $invoice->tr_to_name;
        }
        if (! $trip->goods && $invoice->tr_description_goods) {
            $updates['goods'] = $invoice->tr_description_goods;
        }
        if (! $trip->weight && ($invoice->tr_charged_weight ?? $invoice->tr_weight ?? $invoice->tr_pkg_weight)) {
            $updates['weight'] = $invoice->tr_charged_weight ?? $invoice->tr_weight ?? $invoice->tr_pkg_weight;
        }
        if (! $trip->pickup_date && $invoice->invoice_date) {
            $updates['pickup_date'] = $invoice->invoice_date;
        }
        if (! $trip->lorry_no && ($invoice->tr_truck_no ?? $invoice->tr_lorry_no)) {
            $updates['lorry_no'] = $invoice->tr_truck_no ?? $invoice->tr_lorry_no;
        }

        if (! empty($updates)) {
            $trip->update($updates);
        }
    }

    private function syncLorryFieldsToTrip(Trip $trip, Invoice $invoice): void
    {
        $updates = [];
        if ($invoice->tr_lorry_no) {
            $updates['lorry_no'] = $invoice->tr_lorry_no;
        }
        if ($invoice->tr_owner_party_id) {
            $updates['owner_party_id'] = $invoice->tr_owner_party_id;
        }
        if ($invoice->tr_driver_party_id) {
            $updates['driver_party_id'] = $invoice->tr_driver_party_id;
        }
        if ($invoice->tr_broker_party_id) {
            $updates['broker_party_id'] = $invoice->tr_broker_party_id;
        }

        if (! empty($updates)) {
            $trip->update($updates);
        }
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

    private function logEvent(Trip $trip, ?int $userId, string $type, string $message): void
    {
        TripEvent::query()->create([
            'company_id' => $trip->company_id,
            'trip_id' => $trip->id,
            'user_id' => $userId ?? 1,
            'type' => $type,
            'message' => $message,
            'created_at' => now(),
        ]);
    }
}
