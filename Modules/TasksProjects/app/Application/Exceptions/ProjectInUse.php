<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Exceptions;

/** A project with invoiced time cannot be deleted: the history has to stay. */
final class ProjectInUse extends TasksProjectsException
{
    public static function hasInvoicedTime(int $projectId): self
    {
        return new self("Project {$projectId} has invoiced time entries and cannot be deleted.");
    }
}
