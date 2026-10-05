<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\TasksProjects\Application\TaskTimeSummary;
use Modules\TasksProjects\Models\Task;

/**
 * A task on the board. `board_position` is a decimal string so the fractional
 * ordering survives JSON without a float rewriting it, and `rate` is an
 * override in minor units per hour.
 *
 * `time` is the task's own clock: minutes logged, the billable and still
 * unbilled part of them, whether the task has reached an invoice, and the
 * timers running on it right now. It is filled in by TaskTimeSummary for the
 * whole response at once, and answers zeros for a task nobody has logged time
 * against.
 *
 * @property-read Task $resource
 */
final class TaskResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $task = $this->resource;

        return [
            'id' => (int) $task->id,
            'company_id' => (int) $task->company_id,
            'project_id' => $task->project_id === null ? null : (int) $task->project_id,
            'customer_id' => $task->customer_id === null ? null : (int) $task->customer_id,
            'task_status_id' => (int) $task->task_status_id,
            'number' => (int) $task->number,
            'name' => $task->name,
            'description' => $task->description,
            'assignee_id' => $task->assignee_id === null ? null : (int) $task->assignee_id,
            'priority' => $task->priority,
            'due_date' => $task->due_date?->toDateString(),
            'estimated_minutes' => $task->estimated_minutes === null ? null : (int) $task->estimated_minutes,
            'billable' => (bool) $task->billable,
            'rate' => $task->rate === null ? null : (int) $task->rate,
            'board_position' => (string) $task->board_position,
            'closed_at' => $task->closed_at?->toIso8601String(),
            'creator_id' => $task->creator_id === null ? null : (int) $task->creator_id,
            'created_at' => $task->created_at?->toIso8601String(),
            'updated_at' => $task->updated_at?->toIso8601String(),
            'time' => $task->timeSummary ?? TaskTimeSummary::empty(),
        ];
    }
}
