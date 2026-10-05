<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Exceptions;

/**
 * A fully invoiced task, while the company locks invoiced tasks.
 *
 * The lock is a company setting rather than a rule of the data model: the time
 * on the task is already history either way, and what the setting protects is
 * the description and the status the invoice was raised against.
 */
final class TaskLocked extends TasksProjectsException
{
    public static function forTask(int $taskId): self
    {
        return new self("Task {$taskId} is invoiced and this company locks invoiced tasks.");
    }
}
