<?php

declare(strict_types=1);

namespace Modules\InvoiceReceipt\Http\Controllers;

use App\Domains\Sales\Models\Invoice;
use App\Platform\Http\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsignmentLookupController extends Controller
{
    /**
     * Search LR Receipts by consignment / invoice number, vehicle no, etc.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $query = trim((string) $request->input('query', ''));

        if ($query === '') {
            return response()->json(['data' => []]);
        }

        $lrReceipts = Invoice::query()
            ->whereCompany()
            ->where('template_name', 'lr_receipt')
            ->where(function ($q) use ($query) {
                $q->where('invoice_number', 'LIKE', "{$query}%")
                    ->orWhere('invoice_number', 'LIKE', "%{$query}%")
                    ->orWhere('tr_truck_no', 'LIKE', "%{$query}%");
            })
            ->with(['items', 'customer', 'customer.billingAddress', 'customer.shippingAddress', 'consigneeCustomer'])
            ->latest('invoice_date')
            ->limit(10)
            ->get();

        $data = $lrReceipts->map(fn (Invoice $invoice) => $this->formatLrReceipt($invoice));

        return response()->json(['data' => $data]);
    }

    /**
     * Find a single LR Receipt by exact consignment number.
     */
    public function show(Request $request, string $number): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $trimmed = trim($number);

        $invoice = Invoice::query()
            ->whereCompany()
            ->where('template_name', 'lr_receipt')
            ->where(function ($q) use ($trimmed) {
                $q->where('invoice_number', $trimmed)
                    ->orWhereRaw('LOWER(invoice_number) = ?', [strtolower($trimmed)]);
            })
            ->with(['items', 'customer', 'customer.billingAddress', 'customer.shippingAddress', 'consigneeCustomer'])
            ->first();

        if (! $invoice) {
            return response()->json([
                'message' => 'LR Receipt not found for consignment number: '.$number,
                'data' => null,
            ], 404);
        }

        return response()->json([
            'data' => $this->formatLrReceipt($invoice),
        ]);
    }

    private function formatLrReceipt(Invoice $invoice): array
    {
        $firstItem = $invoice->items->first();

        // Consignment date in YYYY-MM-DD
        $consignmentDate = null;
        if ($invoice->invoice_date) {
            $consignmentDate = Carbon::parse($invoice->invoice_date)->format('Y-m-d');
        } elseif ($firstItem?->tr_consignment_date) {
            $consignmentDate = Carbon::parse($firstItem->tr_consignment_date)->format('Y-m-d');
        }

        // Party invoice number
        $partyInvNo = $invoice->tr_party_invoice_no
            ?: ($firstItem?->tr_party_inv_no
                ?: ($invoice->reference_number ?: ''));

        // From / Destination
        $fromCode = $invoice->tr_from_code
            ?: ($invoice->tr_from_name ?: ($firstItem?->tr_from_code ?: ''));
        $toCode = $invoice->tr_to_code
            ?: ($invoice->tr_to_name ?: ($firstItem?->tr_to_code ?: ''));

        // Vehicle number
        $truckNo = $invoice->tr_truck_no
            ?: ($invoice->tr_lorry_no ?: ($firstItem?->tr_truck_no ?: ''));

        // Pkg
        $pkg = $invoice->tr_packing
            ?: ($invoice->tr_no_of_articles
                ?: ($firstItem?->tr_pkg_weight ?: ''));

        // Weight
        $weight = $invoice->tr_actual_weight
            ?: ($invoice->tr_charged_weight
                ?: ($firstItem?->tr_charged_weight ?: ''));

        // Charges (major units / rupees)
        $rate = $invoice->tr_basic_freight !== null
            ? (float) $invoice->tr_basic_freight
            : ($firstItem?->tr_rate ? (float) $firstItem->tr_rate : 0);

        $otherCharge = $invoice->tr_other_charge !== null
            ? (float) $invoice->tr_other_charge
            : ($firstItem?->tr_other_charge ? (float) $firstItem->tr_other_charge : 0);

        $lrCharge = $invoice->tr_docket_charge !== null
            ? (float) $invoice->tr_docket_charge
            : ($firstItem?->tr_lr_charge ? (float) $firstItem->tr_lr_charge : 0);

        $ddCharge = $invoice->tr_door_delivery !== null
            ? (float) $invoice->tr_door_delivery
            : ($firstItem?->tr_dd_charge ? (float) $firstItem->tr_dd_charge : 0);

        $customer = $invoice->customer;
        $consignee = $invoice->consigneeCustomer;

        return [
            'id' => $invoice->id,
            'consignment_number' => (string) $invoice->invoice_number,
            'consignment_date' => $consignmentDate,
            'party_inv_no' => (string) $partyInvNo,
            'from_code' => (string) $fromCode,
            'to_code' => (string) $toCode,
            'truck_no' => (string) $truckNo,
            'pkg' => (string) $pkg,
            'weight' => (string) $weight,
            'rate' => $rate,
            'other_charge' => $otherCharge,
            'lr_charge' => $lrCharge,
            'dd_charge' => $ddCharge,
            'customer_id' => $invoice->customer_id,
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->name,
                'company_name' => $customer->company_name,
                'contact_name' => $customer->contact_name,
                'phone' => $customer->phone,
                'currency_id' => $customer->currency_id,
                'billing' => $customer->billingAddress ? [
                    'name' => $customer->billingAddress->name,
                    'city' => $customer->billingAddress->city,
                    'state' => $customer->billingAddress->state,
                    'zip' => $customer->billingAddress->zip,
                ] : null,
                'shipping' => $customer->shippingAddress ? [
                    'name' => $customer->shippingAddress->name,
                    'city' => $customer->shippingAddress->city,
                    'state' => $customer->shippingAddress->state,
                    'zip' => $customer->shippingAddress->zip,
                ] : null,
            ] : null,
            'consignee_customer_id' => $invoice->tr_consignee_customer_id,
            'consignee' => $consignee ? [
                'id' => $consignee->id,
                'name' => $consignee->name,
                'contact_name' => $consignee->contact_name,
                'phone' => $consignee->phone,
            ] : null,
            'gst_tax_payable_by' => $invoice->gst_tax_payable_by ?: ($invoice->tr_gst_payable_by ?: $invoice->tr_gst_through),
            'tr_gst_payable_by' => $invoice->tr_gst_payable_by,
        ];
    }
}
