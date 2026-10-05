<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Http\Controllers;

use App\Domains\Sales\Http\Resources\InvoiceResource;
use App\Domains\Sales\Models\Invoice;
use App\Platform\Http\Controller;
use Illuminate\Http\Request;

/**
 * Customer portal: lists Lorry Receipts where the authenticated customer is
 * owner, driver, or broker.
 */
class CustomerLorryReceiptsController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $customerId = auth()->guard('customer')->id();

        $query = Invoice::where('template_name', 'lorry_receipt')
            ->where('status', '<>', 'DRAFT')
            ->where(function ($q) use ($customerId) {
                $q->where('owner_customer_id', $customerId)
                    ->orWhere('driver_customer_id', $customerId)
                    ->orWhere('broker_customer_id', $customerId);
            })
            ->with(['customer', 'currency'])
            ->latest();

        $invoices = $query->paginate($limit);

        return InvoiceResource::collection($invoices)
            ->additional(['meta' => [
                'lorryReceiptTotalCount' => Invoice::where('template_name', 'lorry_receipt')
                    ->where('status', '<>', 'DRAFT')
                    ->where(function ($q) use ($customerId) {
                        $q->where('owner_customer_id', $customerId)
                            ->orWhere('driver_customer_id', $customerId)
                            ->orWhere('broker_customer_id', $customerId);
                    })
                    ->count(),
            ]]);
    }

    public function show(Request $request, $id)
    {
        $customerId = auth()->guard('customer')->id();

        $invoice = Invoice::where('template_name', 'lorry_receipt')
            ->where('status', '<>', 'DRAFT')
            ->where(function ($q) use ($customerId) {
                $q->where('owner_customer_id', $customerId)
                    ->orWhere('driver_customer_id', $customerId)
                    ->orWhere('broker_customer_id', $customerId);
            })
            ->with(['customer', 'currency'])
            ->findOrFail($id);

        return new InvoiceResource($invoice);
    }
}
