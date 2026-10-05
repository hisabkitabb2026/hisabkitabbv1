<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

final class StartTimerRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'task_id' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'billable' => ['sometimes', 'boolean'],
        ];
    }
}
