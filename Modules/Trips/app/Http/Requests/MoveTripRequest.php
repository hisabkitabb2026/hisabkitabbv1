<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveTripRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status_id' => ['required', 'integer'],
            'position' => ['nullable', 'numeric'],
        ];
    }
}
