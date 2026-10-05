<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Application;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TimeEntry;

/**
 * Read-only aggregates over logged time, for a dashboard or a reporting module.
 *
 * Every total is reported per `currency_id` and never converted: a project
 * inherits its customer's currency, an internal project has none, and the host
 * exchange rate logs are not a module concern. Amounts stay in minor units.
 *
 * A viewer without the view-all-time ability only ever aggregates their own
 * time, whatever range they ask for.
 */
final class ReportService
{
    public function __construct(private readonly CompanyDataReader $companyData) {}

    /**
     * @param  string  $from  inclusive start date, Y-m-d
     * @param  string  $to  inclusive end date, Y-m-d
     * @return array{from: string, to: string, totals: list<array{currency_id: int|null, minutes: int, amount: int, billable_minutes: int, billable_amount: int, unbilled_amount: int}>, by_project: list<array<string, mixed>>, by_member: list<array<string, mixed>>, by_customer: list<array<string, mixed>>, by_billable: list<array<string, mixed>>}
     */
    public function summary(int $companyId, string $from, string $to, ?int $viewerUserId, bool $canSeeAll): array
    {
        $query = TimeEntry::query()
            ->forCompany($companyId)
            ->whereNull('running_user_id')
            ->where('started_at', '>=', Carbon::parse($from)->startOfDay())
            ->where('started_at', '<=', Carbon::parse($to)->endOfDay());

        if (! $canSeeAll) {
            $query->where('user_id', $viewerUserId);
        }

        $entries = $query->orderBy('started_at')->orderBy('id')->get();

        $projects = $this->projectNames($companyId, $entries);
        $customers = $this->taskCustomers($companyId, $entries);
        $members = $this->memberNames($companyId);

        return [
            'from' => $from,
            'to' => $to,
            'totals' => $this->rowsFor($entries, static fn (TimeEntry $entry): array => ['currency' => $entry->currency_id]),
            'by_project' => $this->rowsFor($entries, static fn (TimeEntry $entry): array => [
                'currency' => $entry->currency_id,
                'project_id' => $entry->project_id === null ? null : (int) $entry->project_id,
                'label' => $entry->project_id === null ? 'No project' : ($projects[(int) $entry->project_id] ?? "Project {$entry->project_id}"),
            ]),
            'by_member' => $this->rowsFor($entries, static fn (TimeEntry $entry): array => [
                'currency' => $entry->currency_id,
                'user_id' => (int) $entry->user_id,
                'label' => $members[(int) $entry->user_id] ?? 'Removed member',
            ]),
            'by_customer' => $this->rowsFor($entries, static fn (TimeEntry $entry): array => [
                'currency' => $entry->currency_id,
                'customer_id' => $customers[(int) $entry->task_id] ?? null,
            ]),
            'by_billable' => $this->rowsFor($entries, static fn (TimeEntry $entry): array => [
                'currency' => $entry->currency_id,
                'billable' => (bool) $entry->billable,
            ]),
        ];
    }

    /**
     * Sum the entries into one row per distinct set of dimensions, in the order
     * the dimensions first appear.
     *
     * The `currency` dimension is always present and is rendered as
     * `currency_id`, so no row ever adds two currencies together.
     *
     * @param  Collection<int, TimeEntry>  $entries
     * @param  callable(TimeEntry): array<string, mixed>  $dimensions
     * @return list<array<string, mixed>>
     */
    private function rowsFor(Collection $entries, callable $dimensions): array
    {
        $rows = [];

        foreach ($entries as $entry) {
            $values = $dimensions($entry);
            $bucket = implode('|', array_map(static fn (mixed $value): string => match (true) {
                $value === null => '~',
                is_bool($value) => $value ? '1' : '0',
                default => (string) $value,
            }, $values));

            if (! isset($rows[$bucket])) {
                $row = $values;
                $row['currency_id'] = $values['currency'] === null ? null : (int) $values['currency'];
                unset($row['currency']);

                $rows[$bucket] = $row + [
                    'minutes' => 0,
                    'amount' => 0,
                    'billable_minutes' => 0,
                    'billable_amount' => 0,
                    'unbilled_amount' => 0,
                ];
            }

            $rows[$bucket]['minutes'] += (int) $entry->duration_minutes;
            $rows[$bucket]['amount'] += (int) $entry->amount;

            if (! $entry->billable) {
                continue;
            }

            $rows[$bucket]['billable_minutes'] += (int) $entry->duration_minutes;
            $rows[$bucket]['billable_amount'] += (int) $entry->amount;

            if ($entry->invoice_id === null) {
                $rows[$bucket]['unbilled_amount'] += (int) $entry->amount;
            }
        }

        return array_values($rows);
    }

    /**
     * @param  Collection<int, TimeEntry>  $entries
     * @return array<int, string>
     */
    private function projectNames(int $companyId, Collection $entries): array
    {
        $ids = $entries->pluck('project_id')->filter(static fn (?int $id): bool => $id !== null)->unique()->all();

        if ($ids === []) {
            return [];
        }

        return Project::query()
            ->forCompany($companyId)
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->map(static fn (string $name): string => $name)
            ->all();
    }

    /**
     * @param  Collection<int, TimeEntry>  $entries
     * @return array<int, int|null>
     */
    private function taskCustomers(int $companyId, Collection $entries): array
    {
        $ids = $entries->pluck('task_id')->unique()->all();

        if ($ids === []) {
            return [];
        }

        return Task::query()
            ->forCompany($companyId)
            ->whereIn('id', $ids)
            ->get(['id', 'customer_id'])
            ->mapWithKeys(static fn (Task $task): array => [
                (int) $task->id => $task->customer_id === null ? null : (int) $task->customer_id,
            ])
            ->all();
    }

    /** @return array<int, string> */
    private function memberNames(int $companyId): array
    {
        $names = [];

        foreach ($this->companyData->companyMembers($companyId) as $member) {
            $names[(int) $member['id']] = (string) $member['name'];
        }

        return $names;
    }
}
