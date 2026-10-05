<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Support\CompanyContext;

/**
 * Dropping a task between two neighbours.
 *
 * Both neighbours have to be tasks of this company already sitting in the
 * target column, which the board ordering service also insists on; checking it
 * here turns a bad drag into a validation error instead of a failed write.
 */
final class MoveTaskRequest extends ModuleRequest
{
    /** @return array<string, list<string|Exists>> */
    public function rules(): array
    {
        return [
            'task_status_id' => ['required', 'integer', 'min:1'],
            'before_id' => ['nullable', 'integer', 'min:1', $this->neighbour()],
            'after_id' => ['nullable', 'integer', 'min:1', $this->neighbour()],
        ];
    }

    private function neighbour(): Exists
    {
        $context = CompanyContext::fromRequest($this);

        return Rule::exists((new Task)->getTable(), 'id')
            ->where('company_id', $context->companyId)
            ->where('task_status_id', $this->integer('task_status_id'));
    }
}
