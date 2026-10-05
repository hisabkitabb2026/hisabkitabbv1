<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

use Modules\TasksProjects\Http\Controllers\BulkTasksController;

/**
 * One action, applied to a selection of tasks.
 *
 * The status id is required for the status action and refused for the delete
 * one, so a mistyped body never deletes a selection that meant to move.
 */
final class BulkTasksRequest extends ModuleRequest
{
    /** How many tasks one request may carry, which is a screenful many times over. */
    public const MAX_IDS = 200;

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:'.implode(',', BulkTasksController::ACTIONS)],
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_IDS],
            'ids.*' => ['integer', 'min:1'],
            'task_status_id' => [
                'required_if:action,'.BulkTasksController::ACTION_STATUS,
                'prohibited_if:action,'.BulkTasksController::ACTION_DELETE,
                'integer',
                'min:1',
            ],
        ];
    }
}
