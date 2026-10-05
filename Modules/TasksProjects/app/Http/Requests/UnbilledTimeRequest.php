<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

final class UnbilledTimeRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'min:1'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ];
    }
}
