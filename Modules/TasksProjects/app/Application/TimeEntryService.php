<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Modules\TasksProjects\Application\Exceptions\EntriesAlreadyInvoiced;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\ModuleSettings;

/**
 * Time entries typed by hand, as a start and end pair or as a plain duration.
 *
 * The duration is rounded to the company's billing increment when the entry is
 * saved, and the rate is resolved and frozen at the same moment, so a later
 * rate change never rewrites what was already logged.
 */
final class TimeEntryService
{
    /** How many rows a task's time log ever answers with. */
    public const LOG_LIMIT = 500;

    /** The fields an invoice owns once it has been raised against an entry. */
    private const STAMPED_FIELDS = ['task_id', 'started_at', 'ended_at', 'duration_minutes', 'billable'];

    public function __construct(
        private readonly RateResolver $rates,
        private readonly ModuleSettings $settings,
        private readonly TaskService $tasks,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function create(int $companyId, array $attributes): TimeEntry
    {
        $task = $this->tasks->findForCompany($companyId, (int) $attributes['task_id']);
        $userId = (int) $attributes['user_id'];

        $startedAt = isset($attributes['started_at']) ? Carbon::parse($attributes['started_at']) : Carbon::now();
        $endedAt = isset($attributes['ended_at']) ? Carbon::parse($attributes['ended_at']) : null;

        $minutes = Rounding::roundMinutes(
            $this->minutesFrom($attributes, $startedAt, $endedAt),
            $this->settings->roundingMinutes($companyId),
            $this->settings->roundingDirection($companyId),
        );

        $billable = (bool) ($attributes['billable'] ?? $task->billable);
        $rate = array_key_exists('rate', $attributes) && $attributes['rate'] !== null
            ? (int) $attributes['rate']
            : $this->rates->resolve($task, $userId, $this->settings);

        return TimeEntry::query()->create([
            'company_id' => $companyId,
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'user_id' => $userId,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => $minutes,
            'description' => $attributes['description'] ?? null,
            'billable' => $billable,
            'rate' => $rate,
            'amount' => self::amountFor($minutes, $rate),
            'currency_id' => $this->currencyFor($task),
        ]);
    }

    /**
     * Edit an entry, re-rounding the duration.
     *
     * The rate is re-resolved only while the entry is unbilled: once it is
     * stamped with an invoice the money on it belongs to that invoice, and so
     * does the time it was raised for. A stamped entry therefore accepts a new
     * description and nothing else: its minutes are never re-rounded, its rate
     * and amount are left exactly as the invoice recorded them, and an attempt
     * to move the clock, the billable flag or the task is refused rather than
     * quietly ignored.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(int $companyId, int $id, array $attributes): TimeEntry
    {
        $entry = $this->findForCompany($companyId, $id);

        if ($entry->isStamped()) {
            return $this->updateStamped($entry, $attributes);
        }

        $task = $this->tasks->findForCompany($companyId, (int) ($attributes['task_id'] ?? $entry->task_id));

        if ((int) $task->id !== (int) $entry->task_id) {
            $entry->task_id = $task->id;
            $entry->project_id = $task->project_id;
            $entry->currency_id = $this->currencyFor($task);
        }

        if (array_key_exists('started_at', $attributes)) {
            $entry->started_at = $attributes['started_at'] === null ? null : Carbon::parse($attributes['started_at']);
        }

        if (array_key_exists('ended_at', $attributes)) {
            $entry->ended_at = $attributes['ended_at'] === null ? null : Carbon::parse($attributes['ended_at']);
        }

        if (array_key_exists('description', $attributes)) {
            $entry->description = $attributes['description'];
        }

        if (array_key_exists('billable', $attributes)) {
            $entry->billable = (bool) $attributes['billable'];
        }

        $entry->duration_minutes = Rounding::roundMinutes(
            $this->minutesFrom($attributes, $entry->started_at, $entry->ended_at, (int) $entry->duration_minutes),
            $this->settings->roundingMinutes($companyId),
            $this->settings->roundingDirection($companyId),
        );

        $entry->rate = array_key_exists('rate', $attributes) && $attributes['rate'] !== null
            ? (int) $attributes['rate']
            : $this->rates->resolve($task, (int) $entry->user_id, $this->settings);

        $entry->amount = self::amountFor((int) $entry->duration_minutes, (int) $entry->rate);
        $entry->save();

        return $entry;
    }

    /**
     * Save the one field an invoiced entry still owns.
     *
     * Sending the unchanged value of a protected field is not an edit, so a
     * form that posts the whole row back still works; only a real change is
     * refused, and the message names the fields that would have moved.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function updateStamped(TimeEntry $entry, array $attributes): TimeEntry
    {
        $changed = array_values(array_filter(
            self::STAMPED_FIELDS,
            static fn (string $field): bool => self::wouldChange($entry, $field, $attributes),
        ));

        if ($changed !== []) {
            throw EntriesAlreadyInvoiced::forLockedFields((int) $entry->id, $changed);
        }

        if (array_key_exists('description', $attributes)) {
            $entry->description = $attributes['description'];
        }

        $entry->save();

        return $entry;
    }

    /**
     * Whether the request really moves a protected field off its stored value.
     *
     * @param  array<string, mixed>  $attributes
     */
    private static function wouldChange(TimeEntry $entry, string $field, array $attributes): bool
    {
        if (! array_key_exists($field, $attributes)) {
            return false;
        }

        $wanted = $attributes[$field];
        $current = $entry->{$field};

        return match ($field) {
            'started_at', 'ended_at' => $wanted === null || $current === null
                ? $wanted !== $current
                : ! $current->equalTo(Carbon::parse($wanted)),
            'billable' => (bool) $wanted !== (bool) $current,
            default => $wanted !== null && (int) $wanted !== (int) $current,
        };
    }

    /** Invoiced time is history: it can never be deleted from under an invoice. */
    public function delete(int $companyId, int $id): void
    {
        $entry = $this->findForCompany($companyId, $id);

        if ($entry->isStamped()) {
            throw EntriesAlreadyInvoiced::forEntry((int) $entry->id);
        }

        $entry->delete();
    }

    public function findForCompany(int $companyId, int $id): TimeEntry
    {
        $entry = TimeEntry::query()->forCompany($companyId)->find($id);

        if ($entry === null) {
            throw (new ModelNotFoundException)->setModel(TimeEntry::class, [$id]);
        }

        return $entry;
    }

    /**
     * A viewer without the view-all-time ability only ever sees their own time,
     * whatever the filters ask for.
     *
     * @param  array{user_id?: int, project_id?: int, task_id?: int, from?: string, to?: string, billable?: bool, billed?: bool}  $filters
     * @return Collection<int, TimeEntry>
     */
    public function listFor(int $companyId, array $filters, ?int $viewerUserId, bool $canSeeAll): Collection
    {
        $query = TimeEntry::query()->forCompany($companyId)->whereNull('running_user_id');

        if (! $canSeeAll) {
            $query->where('user_id', $viewerUserId);
        } elseif (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        foreach (['project_id', 'task_id'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        if (isset($filters['from'])) {
            $query->where('started_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (isset($filters['to'])) {
            $query->where('started_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        if (isset($filters['billable'])) {
            $query->where('billable', (bool) $filters['billable']);
        }

        if (isset($filters['billed'])) {
            $filters['billed'] ? $query->whereNotNull('invoice_id') : $query->whereNull('invoice_id');
        }

        return $query->orderByDesc('started_at')->orderByDesc('id')->get();
    }

    /**
     * The time log of one task: the running clocks first, then everything
     * logged against it, newest first.
     *
     * A running entry has no duration yet, so it would sort among the oldest
     * rows on `started_at` alone; a CASE every supported database understands
     * lifts it to the top instead. The cap keeps a task somebody has been
     * logging against for years from answering with a megabyte of JSON.
     *
     * @return Collection<int, TimeEntry>
     */
    public function logForTask(
        int $companyId,
        int $taskId,
        ?int $viewerUserId,
        bool $canSeeAll,
        int $limit = self::LOG_LIMIT,
    ): Collection {
        $query = TimeEntry::query()
            ->forCompany($companyId)
            ->where('task_id', $taskId);

        if (! $canSeeAll) {
            $query->where('user_id', $viewerUserId);
        }

        return $query
            ->orderByRaw('CASE WHEN running_user_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /** The cached money on an entry: minutes as hours, times the frozen rate. */
    public static function amountFor(int $minutes, int $rate): int
    {
        return (int) round($minutes / 60 * $rate);
    }

    /** @param array<string, mixed> $attributes */
    private function minutesFrom(array $attributes, ?Carbon $startedAt, ?Carbon $endedAt, int $fallback = 0): int
    {
        if (array_key_exists('duration_minutes', $attributes) && $attributes['duration_minutes'] !== null) {
            return max(0, (int) $attributes['duration_minutes']);
        }

        if ($startedAt !== null && $endedAt !== null) {
            return max(0, (int) round($startedAt->diffInSeconds($endedAt, true) / 60));
        }

        return $fallback;
    }

    private function currencyFor(Task $task): ?int
    {
        $project = $task->project_id === null ? null : $task->project()->first();

        return $project?->currency_id === null ? null : (int) $project->currency_id;
    }
}
