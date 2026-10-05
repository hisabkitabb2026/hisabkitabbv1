<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Illuminate\Support\Facades\DB;
use Modules\TasksProjects\Models\Task;

/**
 * Per-company task numbering.
 *
 * The number is the highest one plus one, read inside a transaction. The row
 * holding it is read and locked rather than `max()` taken, because PostgreSQL
 * refuses FOR UPDATE on an aggregate. Two concurrent writers can still agree
 * on the same number; the unique index on `(company_id, number)` catches that
 * and TaskService retries once.
 */
class TaskNumberSequence
{
    public function next(int $companyId): int
    {
        return DB::transaction(static function () use ($companyId): int {
            $highest = Task::query()
                ->forCompany($companyId)
                ->orderByDesc('number')
                ->lockForUpdate()
                ->value('number');

            return (int) $highest + 1;
        });
    }
}
