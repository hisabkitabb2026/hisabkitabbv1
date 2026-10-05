<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LorryPartyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:OWNER,DRIVER,BROKER',
            'customer_id' => 'nullable|integer',
            // A supplier picked from the host's Suppliers list: the profile
            // links to it instead of a new supplier being created.
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where('company_id', (int) $this->header('company')),
            ],
            'code' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:255',
            'alternate_phone' => 'nullable|string|max:255',
            'pan_number' => 'nullable|string|max:255',
            'gstin' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_holder_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:255',
            'ifsc_code' => 'nullable|string|max:255',
            'upi_id' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,blacklisted',
            'notes' => 'nullable|string',
            'licence_no' => 'nullable|string|max:255',
            'licence_date' => 'nullable|date',
            'licence_issued_by' => 'nullable|string|max:255',
            'rto_address' => 'nullable|string',
            'valid_up_to' => 'nullable|date',
            'place' => 'nullable|string|max:255',
            'advice_no' => 'nullable|string|max:255',
            'advice_date' => 'nullable|date',
            'destination_broker_name' => 'nullable|string|max:255',
            'destination_broker_address' => 'nullable|string',
        ];
    }
}
