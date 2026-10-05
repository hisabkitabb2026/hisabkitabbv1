<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Exceptions;

/** Time entry ids that do not exist in the company the request is scoped to. */
final class UnknownTimeEntries extends TasksProjectsException
{
    /** @param list<int> $entryIds */
    public static function forIds(array $entryIds): self
    {
        return new self('Time entries '.implode(', ', $entryIds).' do not belong to this company.');
    }
}
