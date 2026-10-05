<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

/**
 * Starting the clock from a task row: the task is the URL, the details are the
 * body. `billable` overrides the task's own flag, for a start dialog that asked.
 */
final class StartTaskTimerRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'nullable', 'string'],
            'billable' => ['sometimes', 'boolean'],
        ];
    }
}
