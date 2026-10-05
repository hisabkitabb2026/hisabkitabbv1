<?php

declare(strict_types=1);

namespace Modules\LrReceipt\Http\Controllers;

use App\Domains\Sales\Http\Resources\InvoiceResource;
use App\Domains\Sales\Models\Invoice;
use App\Platform\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\LrReceipt\Support\Abilities;
use Modules\LrReceipt\Support\Authorizes;
use Modules\LrReceipt\Support\CompanyContext;

/**
 * List and show LR Receipts (invoices with template_name = 'lr_receipt').
 */
class LrReceiptController extends Controller
{
    public function __construct(private readonly Authorizes $moduleAuthorizes) {}

    /**
     * A page of receipts. Returns a resource collection, not a JsonResponse —
     * a JsonResponse return type made the call fail with a TypeError.
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::VIEW_LR_RECEIPT);

        $limit = $request->input('limit', 10);
        $filters = array_merge($request->all(), ['template_name' => 'lr_receipt']);

        $invoices = Invoice::query()
            ->whereCompany()
            ->applyFilters($filters)
            ->with(['customer', 'creditNotes:id,related_invoice_id,invoice_number,total'])
            ->latest()
            ->paginateData($limit);

        return InvoiceResource::collection($invoices)
            ->additional([
                'meta' => [
                    'invoice_total_count' => Invoice::query()
                        ->whereCompany()
                        ->where('template_name', 'lr_receipt')
                        ->count(),
                ],
            ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::VIEW_LR_RECEIPT);

        $invoice = Invoice::whereCompany()
            ->where('template_name', 'lr_receipt')
            ->with(['customer', 'items', 'items.fields', 'items.fields.customField', 'taxes', 'fields', 'fields.customField', 'currency'])
            ->findOrFail($id);

        $this->authorize('view', $invoice);

        return response()->json([
            'data' => new InvoiceResource($invoice),
        ]);
    }

    /**
     * Check if the Lorry Receipt for a given LR Receipt (docket) has both
     * Section C (advance_amount) and Section E (net_amount_payable) filled.
     */
    public function lorryReceiptStatus(Request $request, int $id): JsonResponse
    {
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::VIEW_LR_RECEIPT);

        $lrReceipt = Invoice::whereCompany()
            ->where('template_name', 'lr_receipt')
            ->findOrFail($id);

        $docketNumber = $lrReceipt->invoice_number;

        $lorryReceipt = Invoice::where('template_name', 'lorry_receipt')
            ->where('company_id', $lrReceipt->company_id)
            ->where(function ($query) use ($docketNumber) {
                $query->where('received_no_bilties', $docketNumber)
                    ->orWhere('received_no_bilties', 'LIKE', $docketNumber.',%')
                    ->orWhere('received_no_bilties', 'LIKE', '%,'.$docketNumber)
                    ->orWhere('received_no_bilties', 'LIKE', '%,'.$docketNumber.',%');
            })
            ->first();

        if (! $lorryReceipt) {
            return response()->json([
                'has_lorry_receipt' => false,
                'section_c_filled' => false,
                'section_e_filled' => false,
                'is_complete' => false,
                'lorry_receipt_id' => null,
                'lorry_receipt_number' => null,
                'docket_number' => $lrReceipt->invoice_number,
                'message' => 'No Lorry Receipt found for this docket.',
            ]);
        }

        $sectionCFilled = ! blank($lorryReceipt->advance_amount) && (float) $lorryReceipt->advance_amount > 0;

        $sectionEFilled = ! blank($lorryReceipt->final_balance_on)
            || ! blank($lorryReceipt->final_balance_paid_at)
            || ! blank($lorryReceipt->final_cash_cheque_no)
            || ! blank($lorryReceipt->detention_amount)
            || ! blank($lorryReceipt->extra_hire_amount);

        return response()->json([
            'has_lorry_receipt' => true,
            'section_c_filled' => $sectionCFilled,
            'section_e_filled' => $sectionEFilled,
            'is_complete' => $sectionCFilled && $sectionEFilled,
            'lorry_receipt_id' => $lorryReceipt->id,
            'lorry_receipt_number' => $lorryReceipt->invoice_number,
            'docket_number' => $lrReceipt->invoice_number,
        ]);
    }
}
