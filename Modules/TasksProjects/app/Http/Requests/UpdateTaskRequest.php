<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

use Modules\TasksProjects\Models\Task;

final class UpdateTaskRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'project_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'customer_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'task_status_id' => ['sometimes', 'integer', 'min:1'],
            'description' => ['sometimes', 'nullable', 'string'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'priority' => ['sometimes', 'nullable', 'string', 'max:16', 'in:'.implode(',', Task::PRIORITIES)],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'estimated_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'billable' => ['sometimes', 'boolean'],
            'rate' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
