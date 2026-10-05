<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Exceptions;

/** Non-billable time, and time on an internal project, never reaches an invoice. */
final class NotBillable extends TasksProjectsException
{
    /** @param list<int> $entryIds */
    public static function forEntries(array $entryIds): self
    {
        return new self('Time entries '.implode(', ', $entryIds).' are not billable.');
    }

    public static function withoutCustomer(int $entryId): self
    {
        return new self("Time entry {$entryId} is on an internal project and has no customer to bill.");
    }

    public static function running(int $entryId): self
    {
        return new self("Time entry {$entryId} is still running and cannot be invoiced until the timer stops.");
    }
}
