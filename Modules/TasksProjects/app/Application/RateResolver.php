<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Modules\TasksProjects\Models\ProjectMember;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Support\ModuleSettings;

/**
 * Resolves the hourly rate, in minor units, that a time entry should freeze.
 *
 * Order: the task override, the member's rate on the task's project, the
 * project default, the company default from module settings, then zero. The
 * resolved value is written onto the entry, so changing a rate later never
 * rewrites entries that already exist.
 */
final class RateResolver
{
    public function resolve(Task $task, ?int $userId, ModuleSettings $settings): int
    {
        if ($task->rate !== null) {
            return (int) $task->rate;
        }

        $project = $task->project_id === null ? null : $task->project()->first();

        if ($project !== null && $userId !== null) {
            $member = ProjectMember::query()
                ->forCompany((int) $task->company_id)
                ->where('project_id', $project->id)
                ->where('user_id', $userId)
                ->first();

            if ($member !== null && $member->rate !== null) {
                return (int) $member->rate;
            }
        }

        if ($project !== null && $project->default_rate !== null) {
            return (int) $project->default_rate;
        }

        return $settings->defaultRate((int) $task->company_id);
    }
}
