<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

final class StoreTaskStatusRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'colour' => ['nullable', 'string', 'max:16'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['sometimes', 'boolean'],
            'is_closed' => ['sometimes', 'boolean'],
        ];
    }
}
