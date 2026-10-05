<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;
use Modules\TasksProjects\Application\Exceptions\EntriesAlreadyInvoiced;
use Modules\TasksProjects\Application\Exceptions\MixedBillingSelection;
use Modules\TasksProjects\Application\Exceptions\NotBillable;
use Modules\TasksProjects\Application\Exceptions\NothingToInvoice;
use Modules\TasksProjects\Application\Exceptions\UnknownTimeEntries;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\ModuleSettings;

/**
 * Turning unbilled time into invoice lines.
 *
 * What to bill arrives as a BillingSelection, so the three ways a screen can
 * ask (these entries, these tasks, this project) meet in one place and produce
 * the same ordered list of entries, and each line's name and note are composed
 * by InvoiceLineComposer from the company's own settings.
 *
 * The module never writes to the host invoice tables. `prepare()` returns the
 * exact body the host invoice endpoint expects, the browser posts it with the
 * session's own client, and `confirm()` stamps the entries with the ids that
 * came back. That leaves a window in which the invoice exists and the entries
 * are not yet stamped, so `confirm()` is idempotent and `unbilled()` treats an
 * entry whose invoice no longer exists as unbilled again.
 */
final class BillingService
{
    /** The ways a selection can be collapsed into invoice lines. */
    public const GROUPINGS = ['task', 'project', 'member', 'summary'];

    /** The single line produced by the `summary` grouping. */
    public const SUMMARY_LABEL = 'Time';

    /** The grouping a selection falls back to when the caller names none. */
    public const DEFAULT_GROUPING = 'task';

    public function __construct(
        private readonly CompanyDataReader $companyData,
        private readonly ModuleSettings $settings,
        private readonly InvoiceLineComposer $composer,
    ) {}

    /**
     * Billable, unbilled time for one customer, grouped four ways.
     *
     * Entries reach this list through their task's denormalised `customer_id`,
     * which covers both a task on that customer's project and a standalone
     * billable task. Time on an internal project never appears.
     *
     * @param  string|null  $from  inclusive start date, Y-m-d
     * @param  string|null  $to  inclusive end date, Y-m-d
     * @return array{customer_id: int, from: string|null, to: string|null, entry_ids: list<int>, minutes: int, currencies: list<array{currency_id: int|null, minutes: int, amount: int}>, entries: list<array<string, mixed>>, groups: array{task: list<array<string, mixed>>, project: list<array<string, mixed>>, member: list<array<string, mixed>>, summary: list<array<string, mixed>>}}
     */
    public function unbilled(int $companyId, int $customerId, ?string $from = null, ?string $to = null): array
    {
        $entries = $this->unbilledEntries($companyId, $customerId, $from, $to);

        $currencies = [];
        $minutes = 0;
        foreach ($entries as $entry) {
            $key = $entry->currency_id === null ? 'null' : (string) $entry->currency_id;
            $currencies[$key] ??= ['currency_id' => $entry->currency_id, 'minutes' => 0, 'amount' => 0];
            $currencies[$key]['minutes'] += (int) $entry->duration_minutes;
            $currencies[$key]['amount'] += (int) $entry->amount;
            $minutes += (int) $entry->duration_minutes;
        }

        $labels = $this->labelsFor($companyId, $entries);

        return [
            'customer_id' => $customerId,
            'from' => $from,
            'to' => $to,
            'entry_ids' => $entries->map(static fn (TimeEntry $entry): int => (int) $entry->id)->all(),
            'minutes' => $minutes,
            'currencies' => array_values($currencies),
            'entries' => $this->rows($entries, $labels),
            'groups' => [
                'task' => $this->group($entries, 'task', $labels, true),
                'project' => $this->group($entries, 'project', $labels, true),
                'member' => $this->group($entries, 'member', $labels, true),
                'summary' => $this->group($entries, 'summary', $labels, true),
            ],
        ];
    }

    /**
     * Which customers have unbilled billable time, and how much of it.
     *
     * The same rule `unbilled()` applies to one customer, applied to all of
     * them at once: the unbilled time page needs to know who is worth opening
     * before it asks for anyone's entries. A customer whose work spans two
     * currencies gets a row per currency, because money in two denominations
     * cannot be added up and `prepare()` refuses such a selection anyway.
     *
     * @param  string|null  $from  inclusive start date, Y-m-d
     * @param  string|null  $to  inclusive end date, Y-m-d
     * @return list<array{customer_id: int, entries: int, minutes: int, amount: int, currency_id: int|null}>
     */
    public function customers(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $customerByTask = Task::query()
            ->forCompany($companyId)
            ->whereNotNull('customer_id')
            ->pluck('customer_id', 'id');

        $entries = $this->unbilledEntriesForTasks(
            $companyId,
            array_map(intval(...), $customerByTask->keys()->all()),
            $from,
            $to,
        );

        $rows = [];
        foreach ($entries as $entry) {
            $customerId = (int) $customerByTask->get((int) $entry->task_id);
            $currencyId = $entry->currency_id === null ? null : (int) $entry->currency_id;

            $bucket = $customerId.'|'.($currencyId ?? 'null');
            $rows[$bucket] ??= [
                'customer_id' => $customerId,
                'entries' => 0,
                'minutes' => 0,
                'amount' => 0,
                'currency_id' => $currencyId,
            ];

            $rows[$bucket]['entries']++;
            $rows[$bucket]['minutes'] += (int) $entry->duration_minutes;
            $rows[$bucket]['amount'] += (int) $entry->amount;
        }

        $rows = array_values($rows);
        usort($rows, static fn (array $left, array $right): int => [$left['customer_id'], $left['currency_id'] ?? 0]
            <=> [$right['customer_id'], $right['currency_id'] ?? 0]);

        return $rows;
    }

    /**
     * The invoice body for a selection of entries, plus the entry ids behind
     * each line.
     *
     * `groups[i]` lists the entries that produced `items[i]`, so the caller can
     * hand `confirm()` the line ids the host gave back without re-deriving the
     * grouping. A line's `price` is the shared rate of its entries, or the
     * blended rate when they differ, and its `total` is `quantity * price` so
     * the invoice the host builds matches the preview exactly.
     *
     * Every key the host's invoice writer reads is present, including the ones
     * this module never sets: a line carries its zeroed discount and tax fields
     * so `DocumentItemService::createItems` never reaches for a missing index,
     * and `notes` and `template_name` are placeholders the browser fills in from
     * the company's own defaults before it posts.
     *
     * @param  'task'|'project'|'member'|'summary'  $grouping
     * @return array{invoice_date: string, customer_id: int, currency_id: int|null, discount: int, discount_type: string, discount_val: int, tax: int, sub_total: int, total: int, notes: string|null, template_name: string|null, taxes: list<array<string, mixed>>, items: list<array{name: string, description: string|null, quantity: float, price: int, discount_type: string, discount: int, discount_val: int, tax: int, taxes: list<array<string, mixed>>, total: int}>, groups: list<array{entry_ids: list<int>}>}
     */
    public function prepare(int $companyId, BillingSelection $selection, string $grouping = self::DEFAULT_GROUPING): array
    {
        if (! in_array($grouping, self::GROUPINGS, true)) {
            throw new InvalidArgumentException(
                "Grouping '{$grouping}' is not one of ".implode(', ', self::GROUPINGS).'.',
            );
        }

        $entries = $this->resolveEntries($companyId, $selection);
        $tasks = $this->tasksFor($companyId, $entries);
        $customerId = $this->singleCustomerFor($tasks, $entries);
        $currencyId = $this->singleCurrencyFor($entries);

        $labels = $this->labelsFor($companyId, $entries, $tasks);
        $options = $this->settings->invoiceLineOptions($companyId);
        $byId = $entries->keyBy(static fn (TimeEntry $entry): int => (int) $entry->id);

        $items = [];
        $groups = [];
        $subTotal = 0;

        foreach ($this->group($entries, $grouping, $labels, false) as $row) {
            /** @var list<TimeEntry> $rowEntries */
            $rowEntries = array_values(array_map(
                static fn (int $entryId): TimeEntry => $byId->get($entryId),
                $row['entry_ids'],
            ));
            $task = $grouping === 'task' ? $tasks->get($row['key']) : null;

            $quantity = round($row['minutes'] / 60, 2);
            $price = $row['rate'] ?? ($quantity > 0.0 ? (int) round($row['amount'] / $quantity) : 0);
            $total = (int) round($quantity * $price);

            $items[] = [
                'name' => $this->composer->name((string) $row['label'], $task),
                'description' => $this->composer->description(
                    $rowEntries,
                    $options,
                    $this->projectNameFor($grouping, $row, $rowEntries, $task, $labels),
                    $task?->description === null ? null : (string) $task->description,
                ),
                'quantity' => $quantity,
                'price' => $price,
                'discount_type' => 'fixed',
                'discount' => 0,
                'discount_val' => 0,
                'tax' => 0,
                'taxes' => [],
                'total' => $total,
            ];
            $groups[] = ['entry_ids' => $row['entry_ids']];
            $subTotal += $total;
        }

        return [
            'invoice_date' => Carbon::now()->toDateString(),
            'customer_id' => $customerId,
            'currency_id' => $currencyId,
            'discount' => 0,
            'discount_type' => 'fixed',
            'discount_val' => 0,
            'tax' => 0,
            'sub_total' => $subTotal,
            'total' => $subTotal,
            'notes' => null,
            'template_name' => null,
            'taxes' => [],
            'items' => $items,
            'groups' => $groups,
        ];
    }

    /**
     * The entries a selection stands for, ordered by when the work started.
     *
     * An explicit list of entry ids is validated to the letter, because the
     * caller ticked those boxes itself and a silently dropped row would be a
     * silently dropped invoice line. A task or a project instead asks for
     * "whatever is still unbilled here", so the same rule the unbilled list
     * uses applies: stopped, billable, off an internal project, and free of a
     * live invoice. Nothing left to bill is a refusal of its own rather than an
     * empty invoice.
     *
     * @return Collection<int, TimeEntry>
     */
    public function resolveEntries(int $companyId, BillingSelection $selection): Collection
    {
        return match ($selection->kind) {
            BillingSelection::ENTRIES => $this->selectionFor($companyId, $selection->ids),
            BillingSelection::TASKS => $this->unbilledSelection($companyId, $this->ownTaskIds($companyId, $selection->ids)),
            BillingSelection::PROJECT => $this->unbilledSelection($companyId, $this->projectTaskIds($companyId, $selection->projectId())),
            default => throw new InvalidArgumentException(
                "Billing selection '{$selection->kind}' is not one of ".implode(', ', BillingSelection::KINDS).'.',
            ),
        };
    }

    /**
     * Stamp entries with the invoice and line ids the host handed back.
     *
     * Re-running the same call is harmless: an entry already stamped with this
     * invoice and this line is left alone, so the returned count is the number
     * of entries this call actually wrote and a repeat returns zero. An entry
     * belonging to another invoice, or to another company, is refused.
     *
     * @param  list<array{invoice_item_id: int, entry_ids: list<int>}>  $items
     */
    public function confirm(int $companyId, int $invoiceId, array $items): int
    {
        $wanted = [];
        foreach ($items as $item) {
            foreach ($item['entry_ids'] as $entryId) {
                $wanted[(int) $entryId] = (int) $item['invoice_item_id'];
            }
        }

        if ($wanted === []) {
            return 0;
        }

        return DB::transaction(function () use ($companyId, $invoiceId, $wanted): int {
            $entries = TimeEntry::query()
                ->forCompany($companyId)
                ->whereIn('id', array_keys($wanted))
                ->get()
                ->keyBy('id');

            $missing = array_values(array_diff(array_keys($wanted), $entries->keys()->map(intval(...))->all()));
            if ($missing !== []) {
                throw UnknownTimeEntries::forIds($missing);
            }

            $now = Carbon::now();
            $stamped = 0;

            foreach ($wanted as $entryId => $invoiceItemId) {
                /** @var TimeEntry $entry */
                $entry = $entries->get($entryId);

                if ($entry->invoice_id !== null && (int) $entry->invoice_id !== $invoiceId) {
                    throw EntriesAlreadyInvoiced::forOtherInvoice((int) $entry->id, (int) $entry->invoice_id);
                }

                if ((int) $entry->invoice_id === $invoiceId && (int) $entry->invoice_item_id === $invoiceItemId) {
                    continue;
                }

                $entry->invoice_id = $invoiceId;
                $entry->invoice_item_id = $invoiceItemId;
                $entry->invoiced_at ??= $now;
                $entry->save();
                $stamped++;
            }

            return $stamped;
        });
    }

    /** @return Collection<int, TimeEntry> */
    private function unbilledEntries(int $companyId, int $customerId, ?string $from, ?string $to): Collection
    {
        $taskIds = Task::query()
            ->forCompany($companyId)
            ->where('customer_id', $customerId)
            ->pluck('id')
            ->all();

        return $this->unbilledEntriesForTasks($companyId, array_map(intval(...), $taskIds), $from, $to);
    }

    /**
     * The billable, stopped, not-yet-invoiced time logged against these tasks.
     *
     * One customer's list and the whole company's list differ only in which
     * tasks go in, so both ask this: the internal-project exclusion, the date
     * range and the vanished-invoice rule are written once.
     *
     * @param  list<int>  $taskIds
     * @return Collection<int, TimeEntry>
     */
    private function unbilledEntriesForTasks(int $companyId, array $taskIds, ?string $from, ?string $to): Collection
    {
        if ($taskIds === []) {
            /** @var Collection<int, TimeEntry> $none */
            $none = new Collection;

            return $none;
        }

        $internalProjectIds = Project::query()
            ->forCompany($companyId)
            ->whereNull('customer_id')
            ->pluck('id')
            ->all();

        $query = TimeEntry::query()
            ->forCompany($companyId)
            ->where('billable', true)
            ->whereNull('running_user_id')
            ->whereIn('task_id', $taskIds);

        if ($internalProjectIds !== []) {
            $query->where(static function (Builder $inner) use ($internalProjectIds): void {
                $inner->whereNull('project_id')->orWhereNotIn('project_id', $internalProjectIds);
            });
        }

        if ($from !== null) {
            $query->where('started_at', '>=', Carbon::parse($from)->startOfDay());
        }

        if ($to !== null) {
            $query->where('started_at', '<=', Carbon::parse($to)->endOfDay());
        }

        $entries = $query->orderBy('started_at')->orderBy('id')->get();
        $live = $this->liveInvoiceIds($companyId, $entries);

        return $entries
            ->reject(static fn (TimeEntry $entry): bool => $entry->invoice_id !== null && in_array((int) $entry->invoice_id, $live, true))
            ->values();
    }

    /**
     * The subset of the invoices these entries point at that still exists.
     *
     * The module has no delete hook, so a stamped entry whose invoice was
     * removed in the host counts as unbilled again rather than as money that
     * quietly vanished.
     *
     * @param  Collection<int, TimeEntry>  $entries
     * @return list<int>
     */
    private function liveInvoiceIds(int $companyId, Collection $entries): array
    {
        $stamped = $entries
            ->pluck('invoice_id')
            ->filter(static fn (?int $id): bool => $id !== null)
            ->map(intval(...))
            ->unique()
            ->values()
            ->all();

        if ($stamped === []) {
            return [];
        }

        return array_values(array_map(intval(...), $this->companyData->existingInvoiceIds($companyId, $stamped)));
    }

    /**
     * Load and validate an explicit selection: every id has to exist in the
     * company, be billable, be stopped and be free of a live invoice.
     *
     * @param  list<int>  $entryIds
     * @return Collection<int, TimeEntry>
     */
    private function selectionFor(int $companyId, array $entryIds): Collection
    {
        $ids = array_values(array_unique(array_map(intval(...), $entryIds)));
        sort($ids);

        if ($ids === []) {
            throw MixedBillingSelection::empty();
        }

        $entries = TimeEntry::query()
            ->forCompany($companyId)
            ->whereIn('id', $ids)
            ->orderBy('started_at')
            ->orderBy('id')
            ->get();

        $missing = array_values(array_diff($ids, $entries->map(static fn (TimeEntry $entry): int => (int) $entry->id)->all()));
        if ($missing !== []) {
            throw UnknownTimeEntries::forIds($missing);
        }

        $running = $entries->first(static fn (TimeEntry $entry): bool => $entry->running_user_id !== null);
        if ($running !== null) {
            throw NotBillable::running((int) $running->id);
        }

        $notBillable = $entries
            ->reject(static fn (TimeEntry $entry): bool => (bool) $entry->billable)
            ->map(static fn (TimeEntry $entry): int => (int) $entry->id)
            ->values()
            ->all();
        if ($notBillable !== []) {
            throw NotBillable::forEntries($notBillable);
        }

        $live = $this->liveInvoiceIds($companyId, $entries);
        $invoiced = $entries
            ->filter(static fn (TimeEntry $entry): bool => $entry->invoice_id !== null && in_array((int) $entry->invoice_id, $live, true))
            ->map(static fn (TimeEntry $entry): int => (int) $entry->id)
            ->values()
            ->all();
        if ($invoiced !== []) {
            throw EntriesAlreadyInvoiced::forEntries($invoiced);
        }

        return $entries;
    }

    /**
     * Everything still unbilled on these tasks, or a refusal if that is
     * nothing.
     *
     * @param  list<int>  $taskIds
     * @return Collection<int, TimeEntry>
     */
    private function unbilledSelection(int $companyId, array $taskIds): Collection
    {
        $entries = $this->unbilledEntriesForTasks($companyId, $taskIds, null, null);

        if ($entries->isEmpty()) {
            throw NothingToInvoice::forSelection();
        }

        return $entries;
    }

    /**
     * The named tasks, refusing any the company does not own.
     *
     * A task id from another company is a 404 rather than a quietly shorter
     * invoice, which is the same answer `tasks/{id}` gives.
     *
     * @param  list<int>  $taskIds
     * @return list<int>
     */
    private function ownTaskIds(int $companyId, array $taskIds): array
    {
        $found = array_map(intval(...), Task::query()
            ->forCompany($companyId)
            ->whereIn('id', $taskIds)
            ->pluck('id')
            ->all());

        $missing = array_values(array_diff($taskIds, $found));
        if ($missing !== []) {
            throw (new ModelNotFoundException)->setModel(Task::class, $missing);
        }

        return array_values($found);
    }

    /**
     * Every task filed under one of the company's projects.
     *
     * @return list<int>
     */
    private function projectTaskIds(int $companyId, int $projectId): array
    {
        $project = Project::query()->forCompany($companyId)->find($projectId);

        if ($project === null) {
            throw (new ModelNotFoundException)->setModel(Project::class, [$projectId]);
        }

        return array_values(array_map(intval(...), Task::query()
            ->forCompany($companyId)
            ->where('project_id', $project->id)
            ->pluck('id')
            ->all()));
    }

    /**
     * The tasks these entries were logged against, keyed by id.
     *
     * @param  Collection<int, TimeEntry>  $entries
     * @return Collection<int, Task>
     */
    private function tasksFor(int $companyId, Collection $entries): Collection
    {
        /** @var Collection<int, Task> $tasks */
        $tasks = Task::query()
            ->forCompany($companyId)
            ->whereIn('id', $entries->pluck('task_id')->unique()->all())
            ->get()
            ->keyBy('id');

        return $tasks;
    }

    /**
     * The project a line's note may head itself with.
     *
     * A task line follows its own task, a project line is the project, and a
     * line that collapses several projects only gets a heading when all of its
     * work happens to sit in one of them.
     *
     * @param  array{key: int|null, label: string, currency_id: int|null, rate: int|null, entry_ids: list<int>, minutes: int, amount: int, description: string|null}  $row
     * @param  list<TimeEntry>  $entries
     * @param  array{task: array<int, string>, project: array<int, string>, member: array<int, string>}  $labels
     */
    private function projectNameFor(string $grouping, array $row, array $entries, ?Task $task, array $labels): ?string
    {
        $projectId = match ($grouping) {
            'task' => $task?->project_id === null ? null : (int) $task->project_id,
            'project' => $row['key'],
            default => self::singleProjectId($entries),
        };

        return $projectId === null ? null : ($labels['project'][$projectId] ?? null);
    }

    /**
     * The one project these entries share, or null when they span several.
     *
     * @param  list<TimeEntry>  $entries
     */
    private static function singleProjectId(array $entries): ?int
    {
        $projectIds = [];
        foreach ($entries as $entry) {
            if ($entry->project_id === null) {
                return null;
            }

            $projectIds[(int) $entry->project_id] = true;
        }

        return count($projectIds) === 1 ? (int) array_key_first($projectIds) : null;
    }

    /**
     * @param  Collection<int, Task>  $tasks  keyed by id
     * @param  Collection<int, TimeEntry>  $entries
     */
    private function singleCustomerFor(Collection $tasks, Collection $entries): int
    {
        $customerIds = [];
        foreach ($entries as $entry) {
            $customerId = $tasks->get((int) $entry->task_id)?->customer_id;

            if ($customerId === null) {
                throw NotBillable::withoutCustomer((int) $entry->id);
            }

            $customerIds[(int) $customerId] = true;
        }

        if (count($customerIds) > 1) {
            throw MixedBillingSelection::customers(array_keys($customerIds));
        }

        return (int) array_key_first($customerIds);
    }

    /** @param Collection<int, TimeEntry> $entries */
    private function singleCurrencyFor(Collection $entries): ?int
    {
        $currencies = [];
        foreach ($entries as $entry) {
            $currencies[$entry->currency_id === null ? 'null' : (string) $entry->currency_id] = $entry->currency_id;
        }

        if (count($currencies) > 1) {
            throw MixedBillingSelection::currencies(array_keys($currencies));
        }

        $currencyId = reset($currencies);

        return $currencyId === null ? null : (int) $currencyId;
    }

    /**
     * One row per entry, with the names the review step shows.
     *
     * The grouped views answer "how much"; this answers "which work", so the
     * step that ticks entries off can render the task, the project, the member
     * and the day without a second round trip per row.
     *
     * @param  Collection<int, TimeEntry>  $entries
     * @param  array{task: array<int, string>, project: array<int, string>, member: array<int, string>}  $labels
     * @return list<array{id: int, task_id: int, task_name: string, project_id: int|null, project_name: string|null, user_id: int, user_name: string, date: string|null, minutes: int, amount: int, rate: int, currency_id: int|null, description: string|null}>
     */
    private function rows(Collection $entries, array $labels): array
    {
        return $entries->map(static fn (TimeEntry $entry): array => [
            'id' => (int) $entry->id,
            'task_id' => (int) $entry->task_id,
            'task_name' => $labels['task'][(int) $entry->task_id] ?? "Task {$entry->task_id}",
            'project_id' => $entry->project_id === null ? null : (int) $entry->project_id,
            'project_name' => $entry->project_id === null
                ? null
                : ($labels['project'][(int) $entry->project_id] ?? "Project {$entry->project_id}"),
            'user_id' => (int) $entry->user_id,
            'user_name' => $labels['member'][(int) $entry->user_id] ?? 'Removed member',
            'date' => $entry->started_at?->toDateString(),
            'minutes' => (int) $entry->duration_minutes,
            'amount' => (int) $entry->amount,
            'rate' => (int) $entry->rate,
            'currency_id' => $entry->currency_id === null ? null : (int) $entry->currency_id,
            'description' => $entry->description,
        ])->values()->all();
    }

    /**
     * Human labels for every grouping key the entries touch.
     *
     * Member names come from the host reader, so a user who has left the
     * company renders as a removed member rather than as a bare id.
     *
     * @param  Collection<int, TimeEntry>  $entries
     * @param  Collection<int, Task>|null  $tasks  already loaded and keyed by id, when the caller has them
     * @return array{task: array<int, string>, project: array<int, string>, member: array<int, string>}
     */
    private function labelsFor(int $companyId, Collection $entries, ?Collection $tasks = null): array
    {
        $tasks ??= $this->tasksFor($companyId, $entries);

        $projectIds = $entries
            ->pluck('project_id')
            ->merge($tasks->pluck('project_id'))
            ->filter(static fn (?int $id): bool => $id !== null)
            ->unique()
            ->all();
        /** @var Collection<int, Project> $projects */
        $projects = $projectIds === []
            ? new Collection
            : Project::query()->forCompany($companyId)->whereIn('id', $projectIds)->get();

        $members = [];
        foreach ($this->companyData->companyMembers($companyId) as $member) {
            $members[(int) $member['id']] = (string) $member['name'];
        }

        return [
            'task' => $tasks->mapWithKeys(static fn (Task $task): array => [(int) $task->id => (string) $task->name])->all(),
            'project' => $projects->mapWithKeys(static fn (Project $project): array => [(int) $project->id => (string) $project->name])->all(),
            'member' => $members,
        ];
    }

    /**
     * Collapse entries into one row per grouping key, in the order the entries
     * were read.
     *
     * `$splitByCurrency` keeps the browsing view honest about multi-currency
     * work; `prepare()` has already refused a mixed selection, so it groups on
     * the key alone.
     *
     * @param  Collection<int, TimeEntry>  $entries
     * @param  array{task: array<int, string>, project: array<int, string>, member: array<int, string>}  $labels
     * @return list<array{key: int|null, label: string, currency_id: int|null, rate: int|null, entry_ids: list<int>, minutes: int, amount: int, description: string|null}>
     */
    private function group(Collection $entries, string $grouping, array $labels, bool $splitByCurrency): array
    {
        $rows = [];

        foreach ($entries as $entry) {
            [$key, $label] = $this->keyFor($entry, $grouping, $labels);
            $bucket = ($key === null ? '~' : (string) $key).($splitByCurrency ? '|'.($entry->currency_id ?? 'null') : '');

            $rows[$bucket] ??= [
                'key' => $key,
                'label' => $label,
                'currency_id' => $entry->currency_id === null ? null : (int) $entry->currency_id,
                'rate' => (int) $entry->rate,
                'entry_ids' => [],
                'minutes' => 0,
                'amount' => 0,
                'descriptions' => [],
            ];

            $rows[$bucket]['entry_ids'][] = (int) $entry->id;
            $rows[$bucket]['minutes'] += (int) $entry->duration_minutes;
            $rows[$bucket]['amount'] += (int) $entry->amount;

            if ($rows[$bucket]['rate'] !== (int) $entry->rate) {
                $rows[$bucket]['rate'] = null;
            }

            $description = trim((string) ($entry->description ?? ''));
            if ($description !== '' && ! in_array($description, $rows[$bucket]['descriptions'], true)) {
                $rows[$bucket]['descriptions'][] = $description;
            }
        }

        return array_values(array_map(static function (array $row): array {
            $descriptions = $row['descriptions'];
            unset($row['descriptions']);
            $row['description'] = $descriptions === [] ? null : implode("\n", $descriptions);

            return $row;
        }, $rows));
    }

    /**
     * @param  array{task: array<int, string>, project: array<int, string>, member: array<int, string>}  $labels
     * @return array{0: int|null, 1: string}
     */
    private function keyFor(TimeEntry $entry, string $grouping, array $labels): array
    {
        return match ($grouping) {
            'task' => [(int) $entry->task_id, $labels['task'][(int) $entry->task_id] ?? "Task {$entry->task_id}"],
            'project' => $entry->project_id === null
                ? [null, 'No project']
                : [(int) $entry->project_id, $labels['project'][(int) $entry->project_id] ?? "Project {$entry->project_id}"],
            'member' => [(int) $entry->user_id, $labels['member'][(int) $entry->user_id] ?? 'Removed member'],
            default => [null, self::SUMMARY_LABEL],
        };
    }
}
