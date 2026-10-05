<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;
use Modules\TasksProjects\Application\ProjectMemberService;
use Modules\TasksProjects\Application\ProjectService;
use Modules\TasksProjects\Application\TaskService;
use Modules\TasksProjects\Application\TaskStatusService;
use Modules\TasksProjects\Application\TimeEntryService;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TaskStatus;

/**
 * Sample projects, tasks and logged time for the public demo.
 *
 * The host's demo reset calls run() once the demo company, its customers and
 * its owner exist, passing the company to fill. Everything goes through the
 * module's own services, and every date is relative to today, so the board
 * and the timesheets always look current. The time is left unbilled, which
 * is what invoicing from time needs to show anything.
 *
 * A company that already has projects is left alone.
 */
final class DemoSeeder extends Seeder
{
    /**
     * Three projects, filed under the company's first three customers by name;
     * a project with no customer left to take is internal. Rates are minor
     * units per hour.
     *
     * @var list<array{name: string, identifier: string, colour: string, default_rate: int, budget_minutes: int|null, due_in_days: int|null, description: string}>
     */
    private const PROJECTS = [
        [
            'name' => 'Website redesign',
            'identifier' => 'WEB',
            'colour' => '#6366f1',
            'default_rate' => 9500,
            'budget_minutes' => 80 * 60,
            'due_in_days' => 30,
            'description' => 'New marketing site: design system, home and pricing pages, blog migration.',
        ],
        [
            'name' => 'Mobile app launch',
            'identifier' => 'APP',
            'colour' => '#0ea5e9',
            'default_rate' => 11000,
            'budget_minutes' => 120 * 60,
            'due_in_days' => 60,
            'description' => 'First release of the ordering app on iOS and Android.',
        ],
        [
            'name' => 'Monthly support',
            'identifier' => 'SUP',
            'colour' => '#f59e0b',
            'default_rate' => 8500,
            'budget_minutes' => null,
            'due_in_days' => null,
            'description' => 'Hosting, maintenance and small fixes, billed monthly.',
        ],
    ];

    /**
     * Tasks by project (an index into PROJECTS). `status` is a default column
     * name; `entries` are [days ago, minutes, description].
     *
     * @var list<array{project: int, name: string, status: string, priority: string, due_in_days: int, estimated_minutes: int, billable?: bool, entries: list<array{int, int, string}>}>
     */
    private const TASKS = [
        ['project' => 0, 'name' => 'Audit the current site', 'status' => 'Done', 'priority' => Task::PRIORITY_NORMAL, 'due_in_days' => -18, 'estimated_minutes' => 240, 'entries' => [
            [22, 120, 'Crawled the site and listed broken pages'],
            [21, 90, 'Analytics review: top pages and exits'],
        ]],
        ['project' => 0, 'name' => 'Wireframes for the home and pricing pages', 'status' => 'Done', 'priority' => Task::PRIORITY_HIGH, 'due_in_days' => -10, 'estimated_minutes' => 480, 'entries' => [
            [16, 180, 'Home page wireframe, two directions'],
            [15, 150, 'Pricing page wireframe'],
            [14, 60, 'Review call with the client'],
        ]],
        ['project' => 0, 'name' => 'Design system: colours, type and buttons', 'status' => 'Review', 'priority' => Task::PRIORITY_HIGH, 'due_in_days' => 2, 'estimated_minutes' => 360, 'entries' => [
            [8, 150, 'Colour and type scales'],
            [7, 120, 'Buttons, fields and cards'],
        ]],
        ['project' => 0, 'name' => 'Build the new home page', 'status' => 'In Progress', 'priority' => Task::PRIORITY_HIGH, 'due_in_days' => 7, 'estimated_minutes' => 600, 'entries' => [
            [3, 210, 'Hero and feature sections'],
            [1, 165, 'Responsive layout and testimonials'],
        ]],
        ['project' => 0, 'name' => 'Migrate blog posts', 'status' => 'Backlog', 'priority' => Task::PRIORITY_LOW, 'due_in_days' => 21, 'estimated_minutes' => 300, 'entries' => []],
        ['project' => 1, 'name' => 'Sign-in with email and Apple', 'status' => 'In Progress', 'priority' => Task::PRIORITY_URGENT, 'due_in_days' => 4, 'estimated_minutes' => 480, 'entries' => [
            [4, 240, 'Email sign-in and password reset'],
            [2, 135, 'Sign in with Apple'],
        ]],
        ['project' => 1, 'name' => 'Offline cart', 'status' => 'Review', 'priority' => Task::PRIORITY_NORMAL, 'due_in_days' => 3, 'estimated_minutes' => 420, 'entries' => [
            [9, 180, 'Local storage for the cart'],
            [6, 120, 'Sync when the connection returns'],
        ]],
        ['project' => 1, 'name' => 'Push notifications for order updates', 'status' => 'Backlog', 'priority' => Task::PRIORITY_NORMAL, 'due_in_days' => 25, 'estimated_minutes' => 360, 'entries' => []],
        ['project' => 1, 'name' => 'App Store screenshots and listing', 'status' => 'Backlog', 'priority' => Task::PRIORITY_LOW, 'due_in_days' => 35, 'estimated_minutes' => 180, 'entries' => []],
        ['project' => 2, 'name' => 'Renew the SSL certificate', 'status' => 'Done', 'priority' => Task::PRIORITY_URGENT, 'due_in_days' => -5, 'estimated_minutes' => 30, 'entries' => [
            [6, 30, 'Renewed and installed the certificate'],
        ]],
        ['project' => 2, 'name' => 'Fix checkout emails landing in spam', 'status' => 'In Progress', 'priority' => Task::PRIORITY_HIGH, 'due_in_days' => 1, 'estimated_minutes' => 120, 'entries' => [
            [2, 75, 'SPF and DKIM records'],
        ]],
        ['project' => 2, 'name' => 'Update the internal runbook', 'status' => 'Backlog', 'priority' => Task::PRIORITY_LOW, 'due_in_days' => 12, 'estimated_minutes' => 90, 'billable' => false, 'entries' => [
            [5, 45, 'Restore steps for the database'],
        ]],
    ];

    public function run(
        int $companyId,
        CompanyDataReader $companyData,
        TaskStatusService $statuses,
        ProjectService $projects,
        ProjectMemberService $members,
        TaskService $tasks,
        TimeEntryService $entries,
    ): void {
        if (Project::query()->forCompany($companyId)->exists()) {
            return;
        }

        $owner = $companyData->companyMembers($companyId)[0]['id'] ?? null;

        if ($owner === null) {
            return;
        }

        $statuses->ensureDefaults($companyId);
        $columns = $statuses->listFor($companyId)->keyBy(fn (TaskStatus $status): string => $status->name);
        $customers = array_values($companyData->searchCustomers($companyId, null, count(self::PROJECTS)));
        $today = Carbon::today();

        $created = [];

        foreach (self::PROJECTS as $index => $project) {
            $created[$index] = $projects->create($companyId, [
                'customer_id' => $customers[$index]['id'] ?? null,
                'name' => $project['name'],
                'identifier' => $project['identifier'],
                'description' => $project['description'],
                'colour' => $project['colour'],
                'default_rate' => $project['default_rate'],
                'budget_minutes' => $project['budget_minutes'],
                'due_date' => $project['due_in_days'] === null ? null : $today->copy()->addDays($project['due_in_days'])->toDateString(),
                'creator_id' => $owner,
            ]);

            $members->attach($companyId, (int) $created[$index]->id, (int) $owner);
        }

        foreach (self::TASKS as $definition) {
            $task = $tasks->create($companyId, [
                'project_id' => $created[$definition['project']]->id,
                'task_status_id' => $columns->get($definition['status'])?->id,
                'name' => $definition['name'],
                'priority' => $definition['priority'],
                'due_date' => $today->copy()->addDays($definition['due_in_days'])->toDateString(),
                'estimated_minutes' => $definition['estimated_minutes'],
                'billable' => $definition['billable'] ?? true,
                'assignee_id' => $owner,
                'creator_id' => $owner,
            ]);

            foreach ($definition['entries'] as [$daysAgo, $minutes, $description]) {
                $startedAt = $this->workday($today->copy()->subDays($daysAgo))->setTime(9, 30);

                $entries->create($companyId, [
                    'task_id' => $task->id,
                    'user_id' => $owner,
                    'started_at' => $startedAt->toDateTimeString(),
                    'ended_at' => $startedAt->copy()->addMinutes($minutes)->toDateTimeString(),
                    'description' => $description,
                ]);
            }
        }
    }

    /** Weekend time looks odd on a timesheet; it moves back to the Friday. */
    private function workday(Carbon $day): Carbon
    {
        return $day->isWeekend() ? $day->previous(Carbon::FRIDAY) : $day;
    }
}
