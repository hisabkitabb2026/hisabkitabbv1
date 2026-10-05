<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LorryPartyProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'customer_id' => $this->customer_id,
            'supplier_id' => $this->supplier_id,
            'type' => $this->type,
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
            'pan_number' => $this->pan_number,
            'gstin' => $this->gstin,
            'bank_name' => $this->bank_name,
            'bank_account_holder_name' => $this->bank_account_holder_name,
            'bank_account_no' => $this->bank_account_no,
            'ifsc_code' => $this->ifsc_code,
            'upi_id' => $this->upi_id,
            'status' => $this->status,
            'notes' => $this->notes,
            'licence_no' => $this->licence_no,
            'licence_date' => $this->licence_date,
            'licence_issued_by' => $this->licence_issued_by,
            'rto_address' => $this->rto_address,
            'valid_up_to' => $this->valid_up_to,
            'place' => $this->place,
            'advice_no' => $this->advice_no,
            'advice_date' => $this->advice_date,
            'destination_broker_name' => $this->destination_broker_name,
            'destination_broker_address' => $this->destination_broker_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
