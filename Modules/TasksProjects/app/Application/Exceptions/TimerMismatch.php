<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Exceptions;

/**
 * The timer the caller asked to stop is not the timer that is running.
 *
 * Stopping is addressed to a task, so a stale tab that still shows yesterday's
 * play button would otherwise stop whatever happens to be running now. Either
 * nothing runs or it runs somewhere else; both are the same mistake to the UI,
 * which reloads the running timer and shows it where it really is.
 */
final class TimerMismatch extends TasksProjectsException
{
    public static function forTask(int $taskId, ?int $runningTaskId): self
    {
        if ($runningTaskId === null) {
            return new self("No timer is running, so task {$taskId} cannot be stopped.");
        }

        return new self("The running timer is on task {$runningTaskId}, not on task {$taskId}.");
    }
}
