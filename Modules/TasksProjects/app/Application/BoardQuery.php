<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TaskStatus;

/**
 * The board: every status of the company in column order, each with its tasks
 * in board order.
 *
 * This is the widest read in the module, so both queries filter on company_id
 * first and the grouping happens in PHP rather than through a window function
 * that MySQL, PostgreSQL and SQLite would each spell differently.
 */
final class BoardQuery
{
    /** @return list<array{status: TaskStatus, tasks: list<Task>}> */
    public function columns(int $companyId, ?int $projectId = null, ?int $assigneeId = null): array
    {
        $statuses = TaskStatus::query()
            ->forCompany($companyId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $query = Task::query()
            ->forCompany($companyId)
            ->orderBy('task_status_id')
            ->orderBy('board_position')
            ->orderBy('id');

        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        if ($assigneeId !== null) {
            $query->where('assignee_id', $assigneeId);
        }

        $tasks = $query->get()->groupBy('task_status_id');

        $columns = [];
        foreach ($statuses as $status) {
            $columns[] = [
                'status' => $status,
                'tasks' => array_values($tasks->get($status->id, collect())->all()),
            ];
        }

        return $columns;
    }
}
