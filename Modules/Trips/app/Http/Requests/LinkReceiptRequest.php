<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Trips\Models\TripReceipt;

class LinkReceiptRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer'],
            'type' => ['required', 'in:'.implode(',', [TripReceipt::TYPE_LR, TripReceipt::TYPE_LORRY])],
        ];
    }
}
