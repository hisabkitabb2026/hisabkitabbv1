<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

final class UpdateTimeEntryRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'task_id' => ['sometimes', 'integer', 'min:1'],
            'started_at' => ['sometimes', 'nullable', 'date'],
            'ended_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:started_at'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
            'billable' => ['sometimes', 'boolean'],
            'rate' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
