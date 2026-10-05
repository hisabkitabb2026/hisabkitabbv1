<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;
use Modules\TasksProjects\Application\Concerns\SortsLists;
use Modules\TasksProjects\Application\Exceptions\ProjectInUse;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\ProjectMember;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TimeEntry;

/** Project CRUD, archiving and the totals the project detail screen shows. */
final class ProjectService
{
    use SortsLists;

    /**
     * The columns the list may be ordered by. The request rule reads this, so
     * a new key is added here and nowhere else.
     *
     * @var list<string>
     */
    public const SORT_KEYS = ['name', 'status', 'due_date', 'created_at', 'default_rate'];

    /** Newest first, the way the host's own lists open. */
    public const DEFAULT_SORT_KEY = 'created_at';

    public const DEFAULT_SORT_ORDER = 'desc';

    /** @var list<string> */
    private const FIELDS = [
        'customer_id', 'name', 'identifier', 'description', 'colour', 'status',
        'currency_id', 'default_rate', 'budget_minutes', 'due_date', 'creator_id',
    ];

    public function __construct(private readonly CompanyDataReader $companyData) {}

    /**
     * @param  array{status?: string, customer_id?: int, user_id?: int, search?: string, sort_by?: string, sort_order?: string}  $filters
     * @return Collection<int, Project>
     */
    public function listFor(int $companyId, array $filters = []): Collection
    {
        $query = Project::query()->forCompany($companyId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('customer_id', $filters)) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (isset($filters['user_id'])) {
            $query->whereIn('id', ProjectMember::query()
                ->forCompany($companyId)
                ->where('user_id', $filters['user_id'])
                ->select('project_id'));
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';

            $query->where(static function (Builder $inner) use ($search): void {
                $inner->where('name', 'like', $search)->orWhere('identifier', 'like', $search);
            });
        }

        $sorts = $this->sorts();
        [$key, $order] = $this->sortFor($filters, $sorts, self::DEFAULT_SORT_KEY, self::DEFAULT_SORT_ORDER);

        return $this->sortList($query->get(), $sorts[$key], $order);
    }

    /**
     * How each sortable column compares, as a value the sorter can order.
     *
     * Dates become timestamps rather than Carbon instances so the comparison
     * is a plain integer one, and a missing date or rate answers null, which
     * the sorter always puts last.
     *
     * @return array<string, callable(Project): (int|string|null)>
     */
    private function sorts(): array
    {
        return [
            'name' => static fn (Project $project): string => (string) $project->name,
            'status' => static fn (Project $project): string => (string) $project->status,
            'due_date' => static fn (Project $project): ?int => $project->due_date?->getTimestamp(),
            'created_at' => static fn (Project $project): ?int => $project->created_at?->getTimestamp(),
            'default_rate' => static fn (Project $project): ?int => $project->default_rate,
        ];
    }

    public function findForCompany(int $companyId, int $id): Project
    {
        $project = Project::query()->forCompany($companyId)->find($id);

        if ($project === null) {
            throw (new ModelNotFoundException)->setModel(Project::class, [$id]);
        }

        return $project;
    }

    /**
     * A project's currency follows the customer it was filed under, unless the
     * caller named one itself.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(int $companyId, array $attributes): Project
    {
        $values = ['company_id' => $companyId, 'status' => Project::STATUS_ACTIVE];

        foreach (self::FIELDS as $field) {
            if (array_key_exists($field, $attributes)) {
                $values[$field] = $attributes[$field];
            }
        }

        if (! array_key_exists('currency_id', $attributes)) {
            $currencyId = $this->customerCurrency($companyId, $values['customer_id'] ?? null);

            if ($currencyId !== null) {
                $values['currency_id'] = $currencyId;
            }
        }

        return Project::query()->create($values);
    }

    /**
     * A project's customer is denormalised onto its tasks, so changing it
     * rewrites the tasks that follow the project, and moves the project to
     * that customer's currency unless the caller named one itself.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(int $companyId, int $id, array $attributes): Project
    {
        return DB::transaction(function () use ($companyId, $id, $attributes): Project {
            $project = $this->findForCompany($companyId, $id);
            $customerChanged = array_key_exists('customer_id', $attributes)
                && (int) $attributes['customer_id'] !== (int) $project->customer_id;

            foreach (self::FIELDS as $field) {
                if (array_key_exists($field, $attributes)) {
                    $project->{$field} = $attributes[$field];
                }
            }

            if (! array_key_exists('currency_id', $attributes) && array_key_exists('customer_id', $attributes)) {
                $currencyId = $this->customerCurrency($companyId, $project->customer_id);

                if ($currencyId !== null) {
                    $project->currency_id = $currencyId;
                }
            }

            $project->save();

            if ($customerChanged) {
                Task::query()
                    ->forCompany($companyId)
                    ->where('project_id', $project->id)
                    ->get()
                    ->each(function (Task $task) use ($project): void {
                        $task->customer_id = $project->customer_id;
                        $task->save();
                    });
            }

            return $project;
        });
    }

    public function archive(int $companyId, int $id): Project
    {
        return $this->setStatus($companyId, $id, Project::STATUS_ARCHIVED);
    }

    public function unarchive(int $companyId, int $id): Project
    {
        return $this->setStatus($companyId, $id, Project::STATUS_ACTIVE);
    }

    /**
     * Delete a project with its members, tasks and time entries.
     *
     * Invoiced time is history and never disappears, so a project that carries
     * any stamped entry is refused: archive it instead.
     */
    public function delete(int $companyId, int $id): void
    {
        DB::transaction(function () use ($companyId, $id): void {
            $project = $this->findForCompany($companyId, $id);

            $invoiced = TimeEntry::query()
                ->forCompany($companyId)
                ->where('project_id', $project->id)
                ->whereNotNull('invoice_id')
                ->exists();

            if ($invoiced) {
                throw ProjectInUse::hasInvoicedTime((int) $project->id);
            }

            TimeEntry::query()->forCompany($companyId)->where('project_id', $project->id)->delete();
            Task::query()->forCompany($companyId)->where('project_id', $project->id)->delete();
            ProjectMember::query()->forCompany($companyId)->where('project_id', $project->id)->delete();

            $project->delete();
        });
    }

    /**
     * Task counts and logged, billable and unbilled totals for one project.
     *
     * Amounts stay in minor units and are not converted between currencies: a
     * project carries a single currency, inherited from its customer.
     *
     * @return array{tasks: array{total: int, open: int, closed: int}, logged_minutes: int, billable_minutes: int, billable_amount: int, unbilled_amount: int, currency_id: int|null}
     */
    public function totals(Project $project): array
    {
        $companyId = (int) $project->company_id;

        $total = Task::query()->forCompany($companyId)->where('project_id', $project->id)->count();
        $closed = Task::query()->forCompany($companyId)->where('project_id', $project->id)->whereNotNull('closed_at')->count();

        $entries = TimeEntry::query()
            ->forCompany($companyId)
            ->where('project_id', $project->id)
            ->get(['duration_minutes', 'billable', 'amount', 'invoice_id']);

        $loggedMinutes = 0;
        $billableMinutes = 0;
        $billableAmount = 0;
        $unbilledAmount = 0;

        foreach ($entries as $entry) {
            $loggedMinutes += (int) $entry->duration_minutes;

            if (! $entry->billable) {
                continue;
            }

            $billableMinutes += (int) $entry->duration_minutes;
            $billableAmount += (int) $entry->amount;

            if ($entry->invoice_id === null) {
                $unbilledAmount += (int) $entry->amount;
            }
        }

        return [
            'tasks' => ['total' => $total, 'open' => $total - $closed, 'closed' => $closed],
            'logged_minutes' => $loggedMinutes,
            'billable_minutes' => $billableMinutes,
            'billable_amount' => $billableAmount,
            'unbilled_amount' => $unbilledAmount,
            'currency_id' => $project->currency_id === null ? null : (int) $project->currency_id,
        ];
    }

    /**
     * The currency of one customer, read through the host contract.
     *
     * An internal project has no customer and so no currency to inherit, and a
     * customer without one leaves the project's currency alone.
     */
    private function customerCurrency(int $companyId, mixed $customerId): ?int
    {
        if ($customerId === null) {
            return null;
        }

        $currencyId = $this->companyData->findCustomer($companyId, (int) $customerId)['currency_id'] ?? null;

        return $currencyId === null ? null : (int) $currencyId;
    }

    private function setStatus(int $companyId, int $id, string $status): Project
    {
        $project = $this->findForCompany($companyId, $id);
        $project->status = $status;
        $project->save();

        return $project;
    }
}
