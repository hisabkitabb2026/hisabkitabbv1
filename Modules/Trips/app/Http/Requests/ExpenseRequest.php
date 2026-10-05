<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Trips\Models\TripExpense;

class ExpenseRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(TripExpense::CATEGORIES)],
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
            'expense_date' => ['required', 'date'],
        ];
    }
}
