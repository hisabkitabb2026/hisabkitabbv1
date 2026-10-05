<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

final class ReorderTaskStatusesRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'min:1'],
        ];
    }
}
