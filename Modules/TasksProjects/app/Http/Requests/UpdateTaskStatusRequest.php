<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

final class UpdateTaskStatusRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'colour' => ['sometimes', 'nullable', 'string', 'max:16'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'is_default' => ['sometimes', 'boolean'],
            'is_closed' => ['sometimes', 'boolean'],
        ];
    }
}
