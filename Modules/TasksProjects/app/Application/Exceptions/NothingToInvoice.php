<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Exceptions;

/**
 * The selection was understood, and it holds no money.
 *
 * Invoicing a task or a project asks for "whatever is unbilled here", so an
 * empty answer is not a broken request: the work is already invoiced, not
 * billable, or still running. The caller gets a plain message rather than a
 * validation error against a field it filled in correctly.
 */
final class NothingToInvoice extends TasksProjectsException
{
    public static function forSelection(): self
    {
        return new self('No unbilled billable time on the selected tasks.');
    }
}
