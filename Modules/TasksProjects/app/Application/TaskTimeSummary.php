<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TimeEntry;

/**
 * The time block every task carries: how much was logged, how much of it is
 * billable, what is still unbilled and who is running a clock on it right now.
 *
 * A task list is the one place in the module where a per-row query would hurt,
 * so the whole page is summarised in three grouped reads over the time entries
 * of the tasks on it: one for logged minutes, one for the billable money, and
 * one for the running rows. Everything is expressed in plain SQL that MySQL,
 * PostgreSQL and SQLite all speak, and the grouping is done by the database
 * rather than by loading every entry into PHP.
 *
 * Running entries are deliberately outside the totals. Their duration is zero
 * until the timer stops, so counting them would show a clock that has been
 * running for an hour as nothing logged; the UI ticks the running rows itself.
 */
final class TaskTimeSummary
{
    /** No billable time has been logged yet. */
    public const NONE = 'none';

    /** Some billable time is still waiting for an invoice. */
    public const UNINVOICED = 'uninvoiced';

    /** Every billable minute on the task is already stamped onto an invoice. */
    public const INVOICED = 'invoiced';

    /**
     * Hang a summary on each of the given tasks.
     *
     * @param  iterable<Task>  $tasks
     */
    public function attach(int $companyId, iterable $tasks): void
    {
        $tasks = is_array($tasks) ? $tasks : iterator_to_array($tasks);

        if ($tasks === []) {
            return;
        }

        $summaries = $this->forTasks($companyId, array_map(
            static fn (Task $task): int => (int) $task->id,
            array_values($tasks),
        ));

        foreach ($tasks as $task) {
            $task->timeSummary = $summaries[(int) $task->id] ?? self::empty();
        }
    }

    /**
     * The summary of one task, for the rules that only need the state.
     *
     * @return array<string, mixed>
     */
    public function forTask(int $companyId, int $taskId): array
    {
        return $this->forTasks($companyId, [$taskId])[$taskId] ?? self::empty();
    }

    /**
     * Three grouped reads, keyed by task id.
     *
     * @param  list<int>  $taskIds
     * @return array<int, array{logged_minutes: int, billable_minutes: int, unbilled_minutes: int, unbilled_amount: int, invoiced: string, running: list<array{entry_id: int, user_id: int, started_at: string|null}>}>
     */
    public function forTasks(int $companyId, array $taskIds): array
    {
        $taskIds = array_values(array_unique(array_map(intval(...), $taskIds)));

        if ($taskIds === []) {
            return [];
        }

        $summaries = [];
        foreach ($taskIds as $taskId) {
            $summaries[$taskId] = self::empty();
        }

        foreach ($this->loggedMinutes($companyId, $taskIds) as $row) {
            $summaries[(int) $row->task_id]['logged_minutes'] = (int) $row->logged_minutes;
        }

        foreach ($this->billableTotals($companyId, $taskIds) as $row) {
            $summaries[(int) $row->task_id] = [
                ...$summaries[(int) $row->task_id],
                'billable_minutes' => (int) $row->billable_minutes,
                'unbilled_minutes' => (int) $row->unbilled_minutes,
                'unbilled_amount' => (int) $row->unbilled_amount,
                'invoiced' => self::stateFor((int) $row->billable_entries, (int) $row->stamped_entries),
            ];
        }

        foreach ($this->runningEntries($companyId, $taskIds) as $entry) {
            $summaries[(int) $entry->task_id]['running'][] = [
                'entry_id' => (int) $entry->id,
                'user_id' => (int) $entry->user_id,
                'started_at' => $entry->started_at instanceof Carbon ? $entry->started_at->toIso8601String() : null,
            ];
        }

        return $summaries;
    }

    /**
     * The shape a task with no time at all still answers with.
     *
     * @return array{logged_minutes: int, billable_minutes: int, unbilled_minutes: int, unbilled_amount: int, invoiced: string, running: list<array{entry_id: int, user_id: int, started_at: string|null}>}
     */
    public static function empty(): array
    {
        return [
            'logged_minutes' => 0,
            'billable_minutes' => 0,
            'unbilled_minutes' => 0,
            'unbilled_amount' => 0,
            'invoiced' => self::NONE,
            'running' => [],
        ];
    }

    /**
     * Uninvoiced the moment one billable minute is unbilled, invoiced once
     * every billable entry is stamped, and none while nothing billable exists.
     */
    private static function stateFor(int $billableEntries, int $stampedEntries): string
    {
        if ($billableEntries === 0) {
            return self::NONE;
        }

        return $stampedEntries === $billableEntries ? self::INVOICED : self::UNINVOICED;
    }

    /**
     * Everything logged against the task, billable or not.
     *
     * @param  list<int>  $taskIds
     * @return Collection<int, object>
     */
    private function loggedMinutes(int $companyId, array $taskIds)
    {
        return $this->stopped($companyId, $taskIds)
            ->selectRaw('task_id, SUM(duration_minutes) as logged_minutes')
            ->groupBy('task_id')
            ->get();
    }

    /**
     * The billable side, split into what is still unbilled and how many entries
     * carry an invoice, counted with a CASE all three databases understand.
     *
     * @param  list<int>  $taskIds
     * @return Collection<int, object>
     */
    private function billableTotals(int $companyId, array $taskIds)
    {
        return $this->stopped($companyId, $taskIds)
            ->where('billable', true)
            ->selectRaw(implode(', ', [
                'task_id',
                'SUM(duration_minutes) as billable_minutes',
                'SUM(CASE WHEN invoice_id IS NULL THEN duration_minutes ELSE 0 END) as unbilled_minutes',
                'SUM(CASE WHEN invoice_id IS NULL THEN amount ELSE 0 END) as unbilled_amount',
                'COUNT(*) as billable_entries',
                'SUM(CASE WHEN invoice_id IS NULL THEN 0 ELSE 1 END) as stamped_entries',
            ]))
            ->groupBy('task_id')
            ->get();
    }

    /**
     * The clocks running on these tasks, whoever they belong to.
     *
     * Totals are open to anyone who may see the task, so the running rows here
     * carry a user id and nothing else; the time log is where per-member
     * visibility is decided.
     *
     * @param  list<int>  $taskIds
     * @return \Illuminate\Database\Eloquent\Collection<int, TimeEntry>
     */
    private function runningEntries(int $companyId, array $taskIds)
    {
        return TimeEntry::query()
            ->forCompany($companyId)
            ->whereIn('task_id', $taskIds)
            ->whereNotNull('running_user_id')
            ->orderBy('started_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<int>  $taskIds
     * @return Builder<TimeEntry>
     */
    private function stopped(int $companyId, array $taskIds)
    {
        return TimeEntry::query()
            ->forCompany($companyId)
            ->whereIn('task_id', $taskIds)
            ->whereNull('running_user_id');
    }
}
