<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

final class StoreTimeEntryRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'task_id' => ['required', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'billable' => ['sometimes', 'boolean'],
            'rate' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
