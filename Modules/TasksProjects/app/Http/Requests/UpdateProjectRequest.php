<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

use Modules\TasksProjects\Models\Project;

final class UpdateProjectRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'customer_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'identifier' => ['sometimes', 'nullable', 'string', 'max:32'],
            'description' => ['sometimes', 'nullable', 'string'],
            'colour' => ['sometimes', 'nullable', 'string', 'max:16'],
            'status' => ['sometimes', 'string', 'in:'.Project::STATUS_ACTIVE.','.Project::STATUS_ARCHIVED],
            'currency_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'default_rate' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'budget_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'due_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
