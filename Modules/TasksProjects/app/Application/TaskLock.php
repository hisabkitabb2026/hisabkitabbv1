<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Modules\TasksProjects\Application\Exceptions\TaskLocked;
use Modules\TasksProjects\Support\ModuleSettings;

/**
 * The "lock invoiced tasks" setting, in one place.
 *
 * A company that bills by task often wants the task to stop moving once the
 * invoice is out, so what the client was billed for still reads the way it was
 * billed. The rule is the same wherever a task changes, so the check lives here
 * rather than being spelled out again in each writer.
 */
final class TaskLock
{
    public function __construct(
        private readonly ModuleSettings $settings,
        private readonly TaskTimeSummary $summary,
    ) {}

    /** @throws TaskLocked when the company locks invoiced tasks and this one is invoiced */
    public function guard(int $companyId, int $taskId): void
    {
        if (! $this->settings->lockInvoicedTasks($companyId)) {
            return;
        }

        if ($this->summary->forTask($companyId, $taskId)['invoiced'] === TaskTimeSummary::INVOICED) {
            throw TaskLocked::forTask($taskId);
        }
    }
}
