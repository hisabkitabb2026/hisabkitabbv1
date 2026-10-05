<?php

// HisabKitab feature

namespace App\Domains\Sales\Http\Resources;

use App\Domains\Metadata\Http\Resources\CustomFieldValueResource;
use App\Domains\Taxation\Http\Resources\TaxResource;
use App\Support\ModuleExtensions;
use Illuminate\Http\Request;
// HisabKitab feature
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One line of an invoice as the admin API publishes it.
 *
 * The line is a snapshot: the catalogue item it came from is only referenced by
 * id, every figure is stored on the row itself, and each amount is paired with
 * its `base_` twin (the same money converted at the document's exchange rate).
 * Line taxes and custom field values ride along whenever the row has any.
 */
class InvoiceItemResource extends JsonResource
{
    /**
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        $item = $this->resource;

        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'discount_type' => $item->discount_type,
            'price' => $item->price,
            'quantity' => $item->quantity,
            'unit_name' => $item->unit_name,
            'discount' => $item->discount,
            'discount_val' => $item->discount_val,
            'tax' => $item->tax,
            'total' => $item->total,
            'invoice_id' => $item->invoice_id,
            'item_id' => $item->item_id,
            'company_id' => $item->company_id,
            'base_price' => $item->base_price,
            'exchange_rate' => $item->exchange_rate,
            'base_discount_val' => $item->base_discount_val,
            'base_tax' => $item->base_tax,
            'base_total' => $item->base_total,
            'recurring_invoice_id' => $item->recurring_invoice_id,
            'taxes' => $this->when(
                $item->taxes()->exists(),
                fn () => TaxResource::collection($item->taxes)
            ),
            'fields' => $this->when(
                $item->fields()->exists(),
                fn () => CustomFieldValueResource::collection($item->fields)
            ),

            // HisabKitab feature — module-registered extra item fields (tr_*, etc.)
            ...ModuleExtensions::invoiceItemResourceFields($item),

            // Invoice Receipt (office invoice) module: per-item consignment
            // fields are stored in tr_ prefixed columns. Expose them with the
            // non-prefixed names the edit form expects.
            'consignment_number' => $item->tr_consignment_number,
            'consignment_date' => $item->tr_consignment_date,
            'party_inv_no' => $item->tr_party_inv_no,
            'from_code' => $item->tr_from_code,
            'from_name' => $item->tr_from_name,
            'to_code' => $item->tr_to_code,
            'to_name' => $item->tr_to_name,
            'truck_no' => $item->tr_truck_no,
            'pkg' => $item->tr_pkg_weight,
            'weight' => $item->tr_charged_weight,
            'rate' => $item->tr_rate,
            'other_charge' => $item->tr_other_charge,
            'lr_charge' => $item->tr_lr_charge,
            'dd_charge' => $item->tr_dd_charge,
            'amount' => $item->tr_rate,
        ];
    }
}
