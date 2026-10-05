<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

use Modules\TasksProjects\Models\Project;

final class StoreProjectRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'identifier' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
            'colour' => ['nullable', 'string', 'max:16'],
            'status' => ['sometimes', 'string', 'in:'.Project::STATUS_ACTIVE.','.Project::STATUS_ARCHIVED],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'default_rate' => ['nullable', 'integer', 'min:0'],
            'budget_minutes' => ['nullable', 'integer', 'min:0'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
