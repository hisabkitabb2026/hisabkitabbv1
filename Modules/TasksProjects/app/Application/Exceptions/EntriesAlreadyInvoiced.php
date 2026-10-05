<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Exceptions;

/** Time entries that already carry an invoice can never be re-billed or deleted. */
final class EntriesAlreadyInvoiced extends TasksProjectsException
{
    /** @param list<int> $entryIds */
    public static function forEntries(array $entryIds): self
    {
        return new self('Time entries '.implode(', ', $entryIds).' are already on an invoice.');
    }

    public static function forEntry(int $entryId): self
    {
        return self::forEntries([$entryId]);
    }

    /**
     * An edit that would move time an invoice was already raised for.
     *
     * @param  list<string>  $fields
     */
    public static function forLockedFields(int $entryId, array $fields): self
    {
        return new self(
            "Time entry {$entryId} is already on an invoice: ".implode(', ', $fields).' cannot be changed.',
        );
    }

    public static function forOtherInvoice(int $entryId, int $invoiceId): self
    {
        return new self("Time entry {$entryId} is already stamped with invoice {$invoiceId}.");
    }
}
