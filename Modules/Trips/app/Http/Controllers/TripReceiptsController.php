<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Controllers;

use App\Domains\Sales\Models\Invoice;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Trips\Application\TripService;
use Modules\Trips\Http\Requests\LinkReceiptRequest;
use Modules\Trips\Http\Resources\TripResource;
use Modules\Trips\Support\Abilities;
use Modules\Trips\Support\Authorizes;

/**
 * The documents on a trip: LR receipts (revenue) and the Lorry receipt (cost).
 */
final class TripReceiptsController extends Controller
{
    public function __construct(
        Authorizes $authorizes,
        private readonly TripService $trips,
    ) {
        parent::__construct($authorizes);
    }

    /**
     * LR receipts not yet on any trip: the map picker's list. `type=lorry`
     * answers the same list for lorry receipts.
     */
    /**
     * Preview: which lorry receipt would be auto-matched for these LR
     * numbers? The create page shows it before the trip is saved.
     */
    public function lorryMatchPreview(Request $request): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TRIP);

        $invoiceIds = array_map(intval(...), (array) $request->input('invoice_ids', []));
        $invoiceIds = array_values(array_filter($invoiceIds));

        if ($invoiceIds === []) {
            return response()->json(['data' => []]);
        }

        $lrNumbers = Invoice::query()
            ->where('company_id', $context->companyId)
            ->where('template_name', 'lr_receipt')
            ->whereIn('id', $invoiceIds)
            ->pluck('invoice_number')
            ->all();

        $matches = $this->trips->matchLorryReceipts($context->companyId, $lrNumbers);

        return response()->json([
            'data' => $matches->map(fn ($invoice): array => [
                'id' => (int) $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $invoice->customer_id !== null ? (int) $invoice->customer_id : null,
                'customer_name' => $invoice->customer?->name,
                'from_city' => $invoice->tr_from_name,
                'to_city' => $invoice->tr_to_name,
                'goods' => $invoice->tr_description_goods,
                'amount' => $this->trips->receiptAmount($invoice, 'lorry'),
                'invoice_date' => $invoice->invoice_date instanceof DateTimeInterface
                    ? $invoice->invoice_date->format('Y-m-d')
                    : ($invoice->invoice_date !== null ? (string) $invoice->invoice_date : null),
            ]),
        ]);
    }

    public function unlinkedLrs(Request $request): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TRIP);

        $customerId = $request->input('customer_id') ? (int) $request->input('customer_id') : null;

        if ($request->input('type') === 'lorry') {
            $invoices = $this->trips->unlinkedLorryInvoices($context->companyId);
        } else {
            $invoices = $this->trips->unlinkedLrInvoices($context->companyId, $customerId);
        }

        return response()->json([
            'data' => $invoices->map(fn ($invoice): array => [
                'id' => (int) $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $invoice->customer_id !== null ? (int) $invoice->customer_id : null,
                'customer_name' => $invoice->customer?->name,
                'from_city' => $invoice->tr_from_name,
                'to_city' => $invoice->tr_to_name,
                'goods' => $invoice->tr_description_goods,
                'amount' => (int) $invoice->total,
                'invoice_date' => $invoice->invoice_date instanceof DateTimeInterface
                    ? $invoice->invoice_date->format('Y-m-d')
                    : (string) $invoice->invoice_date,
            ])->values(),
        ]);
    }

    /** Link an LR or Lorry receipt to a trip. */
    public function store(LinkReceiptRequest $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TRIP);

        $validated = $request->validated();

        $trip = $this->trips->findForCompany($context->companyId, $id);
        $this->trips->linkReceipt($context->companyId, $context->userId, $trip, (int) $validated['invoice_id'], $validated['type']);

        return response()->json([
            'data' => new TripResource($trip->refresh()->load(['receipts', 'events', 'expenses', 'status'])),
        ], 201);
    }

    public function destroy(Request $request, int $id, int $receiptId): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TRIP);

        $trip = $this->trips->findForCompany($context->companyId, $id);
        $this->trips->unlinkReceipt($context->companyId, $context->userId, $trip, $receiptId);

        return response()->json([
            'data' => new TripResource($trip->refresh()->load(['receipts', 'events', 'expenses', 'status'])),
        ]);
    }

    /**
     * Fetch and link the Invoice Receipt (office invoice) whose "received
     * bilties" field references one of this trip's LR numbers.
     */
    public function fetchInvoiceReceipts(Request $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TRIP);

        $trip = $this->trips->findForCompany($context->companyId, $id);
        $trip = $this->trips->fetchInvoiceReceipts($context->companyId, $context->userId, $trip);

        return response()->json(['data' => new TripResource($trip)]);
    }

    /** Upload a document to the trip. */
    public function uploadPod(Request $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TRIP);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'category' => ['required', 'string', 'in:driver_aadhar,driver_license,driver_pan,owner_aadhar,owner_pan,owner_rc,broker_aadhar,broker_pan,pod'],
        ]);

        $trip = $this->trips->findForCompany($context->companyId, $id);
        $trip = $this->trips->addPod($context->userId, $trip, $request->file('file'), $validated['category']);

        return response()->json(['data' => new TripResource($trip)], 201);
    }

    /** Remove a POD document from the trip. */
    public function destroyPod(Request $request, int $id, int $mediaId): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TRIP);

        $trip = $this->trips->findForCompany($context->companyId, $id);
        $trip = $this->trips->removePod($context->userId, $trip, $mediaId);

        return response()->json(['data' => new TripResource($trip)]);
    }

    /**
     * Stream a POD document inline. The local disk has no public URL, so we
     * serve the file through the API — same pattern as expense receipts.
     */
    public function showDocument(Request $request, int $id, int $mediaId)
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TRIP);

        $trip = $this->trips->findForCompany($context->companyId, $id);
        $media = $trip->getMedia('pod')->firstWhere('id', $mediaId);

        if ($media === null) {
            abort(404);
        }

        return response()->file($media->getPath());
    }
}
