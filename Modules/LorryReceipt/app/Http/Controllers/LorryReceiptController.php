<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Http\Controllers;

use App\Domains\Sales\Http\Resources\InvoiceResource;
use App\Domains\Sales\Models\Invoice;
use App\Platform\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\LorryReceipt\Support\Abilities;
use Modules\LorryReceipt\Support\Authorizes;
use Modules\LorryReceipt\Support\CompanyContext;

/**
 * List and show Lorry Receipts (invoices with template_name = 'lorry_receipt').
 */
class LorryReceiptController extends Controller
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
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::VIEW_LORRY_RECEIPT);

        $limit = $request->input('limit', 10);
        $filters = array_merge($request->all(), ['template_name' => 'lorry_receipt']);

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
                        ->where('template_name', 'lorry_receipt')
                        ->count(),
                ],
            ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->moduleAuthorizes->require(CompanyContext::fromRequest($request), Abilities::VIEW_LORRY_RECEIPT);

        $invoice = Invoice::whereCompany()
            ->where('template_name', 'lorry_receipt')
            ->with(['customer', 'items', 'items.fields', 'items.fields.customField', 'taxes', 'fields', 'fields.customField', 'currency'])
            ->findOrFail($id);

        $this->authorize('view', $invoice);

        return response()->json([
            'data' => new InvoiceResource($invoice),
        ]);
    }
}
