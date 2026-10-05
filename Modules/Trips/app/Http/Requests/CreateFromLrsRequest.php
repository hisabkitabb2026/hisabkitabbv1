<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A trip born from existing LR receipts: one or many (a shared lorry).
 */
class CreateFromLrsRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['required', 'integer', 'distinct'],
            'lorry_invoice_id' => ['nullable', 'integer'],
        ];
    }
}
