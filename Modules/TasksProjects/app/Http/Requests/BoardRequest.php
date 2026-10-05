<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

final class BoardRequest extends ModuleRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'project_id' => ['sometimes', 'integer', 'min:1'],
            'assignee_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
