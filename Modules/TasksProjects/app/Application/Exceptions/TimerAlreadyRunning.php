<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Exceptions;

/** A user may only have one running timer per company. */
final class TimerAlreadyRunning extends TasksProjectsException
{
    public static function forUser(int $userId, int $companyId): self
    {
        return new self("User {$userId} already has a running timer in company {$companyId}.");
    }
}
