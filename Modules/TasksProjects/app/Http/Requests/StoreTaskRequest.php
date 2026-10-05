<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

use Modules\TasksProjects\Models\Task;

final class StoreTaskRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'task_status_id' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'assignee_id' => ['nullable', 'integer', 'min:1'],
            'priority' => ['nullable', 'string', 'max:16', 'in:'.implode(',', Task::PRIORITIES)],
            'due_date' => ['nullable', 'date'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0'],
            'billable' => ['sometimes', 'boolean'],
            'rate' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
