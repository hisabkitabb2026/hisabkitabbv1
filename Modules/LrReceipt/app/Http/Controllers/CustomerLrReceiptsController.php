<?php

declare(strict_types=1);

namespace Modules\LrReceipt\Http\Controllers;

use App\Domains\Sales\Http\Resources\InvoiceResource;
use App\Domains\Sales\Models\Invoice;
use App\Platform\Http\Controller;
use Illuminate\Http\Request;

/**
 * Customer portal: lists LR Receipts where the authenticated customer is
 * consignor or consignee. Derived status from warehouse items.
 */
class CustomerLrReceiptsController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $customerId = auth()->guard('customer')->id();

        $query = Invoice::where('template_name', 'lr_receipt')
            ->where('status', '<>', 'DRAFT')
            ->where(function ($q) use ($customerId) {
                $q->where('customer_id', $customerId)
                    ->orWhere('consignee_customer_id', $customerId);
            })
            ->with(['customer', 'consigneeCustomer', 'items', 'currency'])
            ->latest();

        $invoices = $query->paginate($limit);

        return InvoiceResource::collection($invoices)
            ->additional(['meta' => [
                'lrReceiptTotalCount' => Invoice::where('template_name', 'lr_receipt')
                    ->where('status', '<>', 'DRAFT')
                    ->where(function ($q) use ($customerId) {
                        $q->where('customer_id', $customerId)
                            ->orWhere('consignee_customer_id', $customerId);
                    })
                    ->count(),
            ]]);
    }

    public function show(Request $request, $id)
    {
        $customerId = auth()->guard('customer')->id();

        $invoice = Invoice::where('template_name', 'lr_receipt')
            ->where('status', '<>', 'DRAFT')
            ->where(function ($q) use ($customerId) {
                $q->where('customer_id', $customerId)
                    ->orWhere('consignee_customer_id', $customerId);
            })
            ->with(['customer', 'consigneeCustomer', 'items', 'items.fields', 'items.fields.customField', 'taxes', 'fields', 'fields.customField', 'currency'])
            ->findOrFail($id);

        return new InvoiceResource($invoice);
    }
}
