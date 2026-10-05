<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Exceptions;

/** A board column can only disappear when nothing depends on it. */
final class StatusInUse extends TasksProjectsException
{
    public static function hasTasks(int $statusId, int $taskCount): self
    {
        return new self("Task status {$statusId} still holds {$taskCount} task(s). Move them to another status first.");
    }

    public static function isLast(int $statusId): self
    {
        return new self("Task status {$statusId} is the last status of the company and cannot be deleted.");
    }

    public static function isDefault(int $statusId): self
    {
        return new self("Task status {$statusId} is the default status. Mark another status as the default first.");
    }
}
