<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Modules\TasksProjects\Application\Concerns\DetectsUniqueViolations;
use Modules\TasksProjects\Application\Exceptions\TimerAlreadyRunning;
use Modules\TasksProjects\Application\Exceptions\TimerMismatch;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\ModuleSettings;

/**
 * The running timer: one open time entry per user per company.
 *
 * A running entry is an ordinary time entry with `running_user_id` set and
 * `ended_at` still null. The invariant is held by the unique index on
 * `(company_id, running_user_id)` rather than by a read-then-write check, so
 * two tabs racing each other still end up with one timer: the loser's insert
 * fails and is reported as the same TimerAlreadyRunning the check raises.
 *
 * Nothing is rated while the clock runs. The rate is resolved and frozen at
 * stop, together with the rounded duration and the cached amount.
 */
final class TimerService
{
    use DetectsUniqueViolations;

    public function __construct(
        private readonly TaskService $tasks,
        private readonly RateResolver $rates,
        private readonly ModuleSettings $settings,
    ) {}

    /** The caller's running entry, or null when the clock is not running. */
    public function running(int $companyId, int $userId): ?TimeEntry
    {
        return TimeEntry::query()
            ->forCompany($companyId)
            ->where('running_user_id', $userId)
            ->first();
    }

    /**
     * Put the caller's clock on a task.
     *
     * Starting the task the clock is already on is the same wish twice rather
     * than a conflict, so it applies whatever the caller sent and answers the
     * entry that is already running. That is what makes "create this task and
     * start it" correct when `auto_start_tasks` started the clock on create.
     *
     * @throws TimerAlreadyRunning when the user's timer is on another task
     */
    public function start(
        int $companyId,
        int $userId,
        int $taskId,
        ?string $description = null,
        ?bool $billable = null,
    ): TimeEntry {
        $task = $this->tasks->findForCompany($companyId, $taskId);
        $running = $this->running($companyId, $userId);

        if ($running !== null) {
            if ((int) $running->task_id !== (int) $task->id) {
                throw TimerAlreadyRunning::forUser($userId, $companyId);
            }

            return $this->applyDetails($running, $description, $billable);
        }

        try {
            return TimeEntry::query()->create([
                'company_id' => $companyId,
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'user_id' => $userId,
                'started_at' => Carbon::now(),
                'ended_at' => null,
                'duration_minutes' => 0,
                'description' => $description,
                'billable' => $billable ?? (bool) $task->billable,
                'rate' => 0,
                'amount' => 0,
                'currency_id' => $this->currencyFor($task),
                'running_user_id' => $userId,
            ]);
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                throw TimerAlreadyRunning::forUser($userId, $companyId);
            }

            throw $exception;
        }
    }

    /**
     * Close the running entry: derive the elapsed minutes, round them to the
     * company increment, resolve the rate and cache the amount.
     *
     * The description and the billable flag are what the stop dialog collected,
     * and each is applied only when it was sent: a client that stops without a
     * body keeps whatever the start recorded.
     */
    public function stop(
        int $companyId,
        int $userId,
        ?string $description = null,
        ?bool $billable = null,
    ): TimeEntry {
        $entry = $this->requireRunning($companyId, $userId);
        $endedAt = Carbon::now();
        $startedAt = $entry->started_at ?? $endedAt;

        $this->writeDetails($entry, $description, $billable);

        $entry->ended_at = $endedAt;
        $entry->running_user_id = null;
        $entry->duration_minutes = Rounding::roundMinutes(
            max(0, (int) round($startedAt->diffInSeconds($endedAt, true) / 60)),
            $this->settings->roundingMinutes($companyId),
            $this->settings->roundingDirection($companyId),
        );

        $task = $this->tasks->findForCompany($companyId, (int) $entry->task_id);
        $entry->rate = $this->rates->resolve($task, $userId, $this->settings);
        $entry->amount = TimeEntryService::amountFor((int) $entry->duration_minutes, (int) $entry->rate);
        $entry->save();

        return $entry;
    }

    /**
     * Stop the clock the caller is running on one particular task.
     *
     * Stopping is addressed to a task rather than to "whatever is running", so
     * a stale row or a second tab cannot stop a timer the user has since moved
     * elsewhere. Nothing running and something else running are the same
     * mismatch to the caller, who reloads the timer either way.
     *
     * @throws TimerMismatch when the caller's timer is not on this task
     */
    public function stopOn(
        int $companyId,
        int $userId,
        int $taskId,
        ?string $description = null,
        ?bool $billable = null,
    ): TimeEntry {
        $task = $this->tasks->findForCompany($companyId, $taskId);
        $running = $this->running($companyId, $userId);

        if ($running === null || (int) $running->task_id !== (int) $task->id) {
            throw TimerMismatch::forTask(
                (int) $task->id,
                $running === null ? null : (int) $running->task_id,
            );
        }

        return $this->stop($companyId, $userId, $description, $billable);
    }

    /** Throw away the running entry without recording any time. */
    public function discard(int $companyId, int $userId): void
    {
        $this->requireRunning($companyId, $userId)->delete();
    }

    /** Apply the caller's details to a running entry and save if anything moved. */
    private function applyDetails(TimeEntry $entry, ?string $description, ?bool $billable): TimeEntry
    {
        $this->writeDetails($entry, $description, $billable);

        if ($entry->isDirty()) {
            $entry->save();
        }

        return $entry;
    }

    /**
     * Write onto an entry whatever the caller actually sent.
     *
     * Null is "the caller did not say", never "clear it", so a stop that
     * carries only a description leaves the billable flag the start chose.
     */
    private function writeDetails(TimeEntry $entry, ?string $description, ?bool $billable): void
    {
        if ($description !== null) {
            $entry->description = $description;
        }

        if ($billable !== null) {
            $entry->billable = $billable;
        }
    }

    private function requireRunning(int $companyId, int $userId): TimeEntry
    {
        $entry = $this->running($companyId, $userId);

        if ($entry === null) {
            throw (new ModelNotFoundException)->setModel(TimeEntry::class);
        }

        return $entry;
    }

    private function currencyFor(Task $task): ?int
    {
        $project = $task->project_id === null ? null : $task->project()->first();

        return $project?->currency_id === null ? null : (int) $project->currency_id;
    }
}
