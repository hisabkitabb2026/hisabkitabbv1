<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from_city' => ['nullable', 'string', 'max:255'],
            'to_city' => ['nullable', 'string', 'max:255'],
            'goods' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'string', 'max:64'],
            'e_way_bill' => ['nullable', 'string', 'max:64'],
            'pickup_date' => ['nullable', 'date'],
            'delivered_date' => ['nullable', 'date'],
            'lorry_no' => ['nullable', 'string', 'max:64'],
            'owner_party_id' => ['nullable', 'integer'],
            'driver_party_id' => ['nullable', 'integer'],
            'broker_party_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
