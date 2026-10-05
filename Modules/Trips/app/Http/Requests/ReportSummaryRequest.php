<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReportSummaryRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ];
    }
}
