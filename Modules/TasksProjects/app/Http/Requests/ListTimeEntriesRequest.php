<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

final class ListTimeEntriesRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'integer', 'min:1'],
            'project_id' => ['sometimes', 'integer', 'min:1'],
            'task_id' => ['sometimes', 'integer', 'min:1'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'billable' => ['sometimes', 'boolean'],
            'billed' => ['sometimes', 'boolean'],
        ];
    }
}
