<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application\Concerns;

use Illuminate\Database\QueryException;

/**
 * Recognise a unique index violation across MySQL, PostgreSQL and SQLite.
 *
 * The module leans on unique indexes for two invariants that a portable query
 * cannot express: the per-company task number and the single running timer.
 * Both need to tell a collision apart from any other write failure.
 */
trait DetectsUniqueViolations
{
    private function isUniqueViolation(QueryException $exception): bool
    {
        if (! in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
            return false;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'unique') || str_contains($message, 'duplicate entry');
    }
}
