<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use InvalidArgumentException;

/**
 * What the caller asked to invoice, before it becomes a list of time entries.
 *
 * Three shapes reach `billing/prepare`: the explicit entries the unbilled time
 * page ticks off, the tasks a row or a bulk selection names, and a whole
 * project. They differ only in how the entries are found, so the difference is
 * carried here rather than in three overloads of the service, and
 * `BillingService::resolveEntries()` is the single place that turns any of
 * them into the same ordered collection.
 */
final class BillingSelection
{
    /** Entry ids the caller listed itself. */
    public const ENTRIES = 'entries';

    /** Tasks whose unbilled billable time is wanted. */
    public const TASKS = 'tasks';

    /** One project, meaning every task filed under it. */
    public const PROJECT = 'project';

    /** @var list<string> */
    public const KINDS = [self::ENTRIES, self::TASKS, self::PROJECT];

    /** @param list<int> $ids */
    private function __construct(
        public readonly string $kind,
        public readonly array $ids,
    ) {}

    /** @param list<int> $entryIds */
    public static function fromEntryIds(array $entryIds): self
    {
        return new self(self::ENTRIES, self::normalise($entryIds));
    }

    /** @param list<int> $taskIds */
    public static function fromTaskIds(array $taskIds): self
    {
        return new self(self::TASKS, self::normalise($taskIds));
    }

    public static function fromProject(int $projectId): self
    {
        return new self(self::PROJECT, [$projectId]);
    }

    /** The project this selection names, for the project shape only. */
    public function projectId(): int
    {
        if ($this->kind !== self::PROJECT) {
            throw new InvalidArgumentException("A {$this->kind} selection does not name a project.");
        }

        return $this->ids[0];
    }

    /**
     * Ids as integers, de-duplicated and in ascending order.
     *
     * The order the browser sent is never meaningful: entries come back sorted
     * by their start, so sorting here only makes the resolution deterministic
     * and the "which ids are missing" message stable.
     *
     * @param  list<int>  $ids
     * @return list<int>
     */
    private static function normalise(array $ids): array
    {
        $ids = array_values(array_unique(array_map(intval(...), $ids)));
        sort($ids);

        return $ids;
    }
}
