<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

use Modules\TasksProjects\Application\TaskService;

final class ListTasksRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'project_id' => ['sometimes', 'integer', 'min:1'],
            'assignee_id' => ['sometimes', 'integer', 'min:1'],
            'task_status_id' => ['sometimes', 'integer', 'min:1'],
            'customer_id' => ['sometimes', 'integer', 'min:1'],
            'invoiced' => ['sometimes', 'boolean'],
            'due_before' => ['sometimes', 'date'],
            'due_after' => ['sometimes', 'date'],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort_by' => ['sometimes', 'string', 'in:'.implode(',', TaskService::SORT_KEYS)],
            'sort_order' => ['sometimes', 'string', 'in:asc,desc'],
        ];
    }
}
