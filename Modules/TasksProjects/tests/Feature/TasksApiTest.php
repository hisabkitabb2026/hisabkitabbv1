<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TaskStatus;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Support\ModuleSettings;
use Modules\TasksProjects\Tests\TestCase;

final class TasksApiTest extends TestCase
{
    private const COMPANY = 9;

    private const OTHER_COMPANY = 10;

    private const CUSTOMER = 42;

    public function test_the_task_number_is_a_per_company_sequence(): void
    {
        $first = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks', ['name' => 'First']);
        $second = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks', ['name' => 'Second']);
        $elsewhere = $this->asCompany(self::OTHER_COMPANY)->postJson('/api/v1/tasks-projects/tasks', ['name' => 'Theirs']);

        $first->assertCreated()->assertJsonPath('data.number', 1);
        $second->assertCreated()->assertJsonPath('data.number', 2);
        $elsewhere->assertCreated()->assertJsonPath('data.number', 1);
    }

    public function test_a_new_task_lands_in_the_default_column_and_takes_the_projects_customer(): void
    {
        $project = $this->makeProject(self::COMPANY, ['customer_id' => self::CUSTOMER]);

        $response = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks', [
            'name' => 'Landing page',
            'project_id' => $project->id,
            'priority' => Task::PRIORITY_HIGH,
            'estimated_minutes' => 120,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.customer_id', self::CUSTOMER);
        $response->assertJsonPath('data.creator_id', self::DEFAULT_USER);
        $response->assertJsonPath('data.billable', true);
        $response->assertJsonPath('data.priority', Task::PRIORITY_HIGH);
        $response->assertJsonPath('data.closed_at', null);

        $default = TaskStatus::query()->forCompany(self::COMPANY)->where('is_default', true)->firstOrFail();
        $response->assertJsonPath('data.task_status_id', (int) $default->id);
    }

    public function test_a_standalone_task_can_carry_its_own_customer(): void
    {
        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks', ['name' => 'Ad hoc call', 'customer_id' => self::CUSTOMER])
            ->assertCreated()
            ->assertJsonPath('data.project_id', null)
            ->assertJsonPath('data.customer_id', self::CUSTOMER);
    }

    public function test_entering_a_closed_column_stamps_closed_at_and_leaving_it_clears_it(): void
    {
        $open = $this->makeStatus(self::COMPANY, ['name' => 'Backlog']);
        $done = $this->makeStatus(self::COMPANY, ['name' => 'Done', 'position' => 2, 'is_default' => false, 'is_closed' => true]);
        $task = $this->makeTask(self::COMPANY, ['task_status_id' => $open->id]);

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/tasks/'.$task->id, ['task_status_id' => $done->id])
            ->assertOk()
            ->assertJsonPath('data.task_status_id', (int) $done->id);

        self::assertNotNull(Task::query()->findOrFail($task->id)->closed_at);

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/tasks/'.$task->id, ['task_status_id' => $open->id])
            ->assertOk()
            ->assertJsonPath('data.closed_at', null);
    }

    public function test_the_list_filters_by_project_assignee_status_due_date_and_text(): void
    {
        $project = $this->makeProject(self::COMPANY);
        $status = $this->makeStatus(self::COMPANY);
        $other = $this->makeStatus(self::COMPANY, ['name' => 'Done', 'position' => 2, 'is_default' => false]);

        $landing = $this->makeTask(self::COMPANY, [
            'task_status_id' => $status->id,
            'project_id' => $project->id,
            'assignee_id' => 8,
            'name' => 'Landing page',
            'due_date' => '2026-09-10',
        ]);
        $pricing = $this->makeTask(self::COMPANY, [
            'task_status_id' => $other->id,
            'name' => 'Pricing table',
            'due_date' => '2026-10-10',
        ]);

        $this->assertListReturns([$landing->id], '?project_id='.$project->id);
        $this->assertListReturns([$landing->id], '?assignee_id=8');
        $this->assertListReturns([$pricing->id], '?task_status_id='.$other->id);
        $this->assertListReturns([$landing->id], '?due_before=2026-09-30');
        $this->assertListReturns([$pricing->id], '?due_after=2026-09-30');
        $this->assertListReturns([$pricing->id], '?search=Pricing');
    }

    public function test_the_list_opens_by_number_and_sorts_by_every_supported_key(): void
    {
        $this->fourTasks();

        self::assertSame(['zebra', 'apple', 'Mango', 'berry'], $this->names(''));
        self::assertSame(['berry', 'Mango', 'apple', 'zebra'], $this->names('sort_by=number&sort_order=desc'));
        self::assertSame(['apple', 'berry', 'Mango', 'zebra'], $this->names('sort_by=name'));
        self::assertSame(['zebra', 'Mango', 'berry', 'apple'], $this->names('sort_by=name&sort_order=desc'));
        self::assertSame(['apple', 'berry', 'zebra', 'Mango'], $this->names('sort_by=due_date'));
        self::assertSame(['zebra', 'berry', 'apple', 'Mango'], $this->names('sort_by=due_date&sort_order=desc'));
        self::assertSame(['zebra', 'apple', 'Mango', 'berry'], $this->names('sort_by=created_at'));
        self::assertSame(['berry', 'Mango', 'apple', 'zebra'], $this->names('sort_by=created_at&sort_order=desc'));
    }

    public function test_priority_sorts_by_rank_rather_than_by_name(): void
    {
        $this->fourTasks();

        // Alphabetically these run HIGH, LOW, NORMAL, URGENT, which is not an
        // order anyone means; the rank runs LOW, NORMAL, HIGH, URGENT, and the
        // task with no priority set trails both ways.
        self::assertSame(['zebra', 'Mango', 'apple', 'berry'], $this->names('sort_by=priority'));
        self::assertSame(['apple', 'Mango', 'zebra', 'berry'], $this->names('sort_by=priority&sort_order=desc'));
    }

    public function test_the_task_sort_refuses_a_key_or_direction_it_does_not_know(): void
    {
        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks?sort_by=board_position')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks?sort_by=number&sort_order=up')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort_order']);
    }

    public function test_the_list_is_paged_and_never_leaves_the_company(): void
    {
        $this->makeTask(self::COMPANY, ['name' => 'Mine']);
        $this->makeTask(self::OTHER_COMPANY, ['name' => 'Theirs']);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('data.0.name', 'Mine');
    }

    public function test_a_move_lands_between_its_new_neighbours(): void
    {
        $status = $this->makeStatus(self::COMPANY);
        $target = $this->makeStatus(self::COMPANY, ['name' => 'In Progress', 'position' => 2, 'is_default' => false]);

        $first = $this->makeTask(self::COMPANY, ['task_status_id' => $target->id, 'board_position' => '1024.0000000000']);
        $second = $this->makeTask(self::COMPANY, ['task_status_id' => $target->id, 'board_position' => '2048.0000000000']);
        $dragged = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id]);

        $response = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$dragged->id.'/move', [
            'task_status_id' => $target->id,
            'before_id' => $first->id,
            'after_id' => $second->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.task_status_id', (int) $target->id);
        $response->assertJsonPath('data.board_position', '1536.0000000000');
    }

    public function test_a_move_to_the_end_of_a_column_appends(): void
    {
        $status = $this->makeStatus(self::COMPANY);
        $last = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id, 'board_position' => '2048.0000000000']);
        $dragged = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id, 'board_position' => '1024.0000000000']);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/'.$dragged->id.'/move', [
                'task_status_id' => $status->id,
                'before_id' => $last->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.board_position', '3072.0000000000');
    }

    public function test_a_move_refuses_a_neighbour_outside_the_target_column(): void
    {
        $status = $this->makeStatus(self::COMPANY);
        $target = $this->makeStatus(self::COMPANY, ['name' => 'In Progress', 'position' => 2, 'is_default' => false]);
        $stranger = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id]);
        $dragged = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id]);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/'.$dragged->id.'/move', [
                'task_status_id' => $target->id,
                'before_id' => $stranger->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['before_id']);
    }

    public function test_the_board_groups_tasks_into_columns_in_board_order(): void
    {
        $backlog = $this->makeStatus(self::COMPANY, ['name' => 'Backlog']);
        $doing = $this->makeStatus(self::COMPANY, ['name' => 'In Progress', 'position' => 2, 'is_default' => false]);

        $second = $this->makeTask(self::COMPANY, ['task_status_id' => $backlog->id, 'name' => 'Second', 'board_position' => '2048.0000000000']);
        $first = $this->makeTask(self::COMPANY, ['task_status_id' => $backlog->id, 'name' => 'First', 'board_position' => '1024.0000000000']);
        $this->makeTask(self::COMPANY, ['task_status_id' => $doing->id, 'name' => 'Underway']);
        $this->makeTask(self::OTHER_COMPANY, ['name' => 'Theirs']);

        $response = $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/board');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.status.name', 'Backlog');
        $response->assertJsonPath('data.0.tasks.0.id', (int) $first->id);
        $response->assertJsonPath('data.0.tasks.1.id', (int) $second->id);
        $response->assertJsonPath('data.1.status.name', 'In Progress');
        $response->assertJsonPath('data.1.tasks.0.name', 'Underway');
    }

    public function test_the_board_seeds_the_default_columns_and_filters_by_project_and_assignee(): void
    {
        $project = $this->makeProject(self::COMPANY);

        $response = $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/board');

        $response->assertOk();
        $response->assertJsonCount(4, 'data');
        $response->assertJsonPath('data.0.status.name', 'Backlog');

        $status = TaskStatus::query()->forCompany(self::COMPANY)->where('is_default', true)->firstOrFail();
        $mine = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id, 'project_id' => $project->id, 'assignee_id' => 8]);
        $this->makeTask(self::COMPANY, ['task_status_id' => $status->id, 'name' => 'Unassigned']);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/board?project_id='.$project->id)
            ->assertJsonCount(1, 'data.0.tasks')
            ->assertJsonPath('data.0.tasks.0.id', (int) $mine->id);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/board?assignee_id=8')
            ->assertJsonCount(1, 'data.0.tasks')
            ->assertJsonPath('data.0.tasks.0.id', (int) $mine->id);
    }

    public function test_deleting_a_task_takes_its_uninvoiced_time_with_it(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $this->makeEntry(self::COMPANY, (int) $task->id);

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/tasks/'.$task->id)
            ->assertOk()
            ->assertJson(['success' => true]);

        self::assertSame(0, Task::query()->forCompany(self::COMPANY)->count());
    }

    public function test_deleting_a_task_with_invoiced_time_is_refused(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['invoice_id' => 77, 'invoice_item_id' => 88]);

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/tasks/'.$task->id)
            ->assertStatus(422)
            ->assertJsonPath('error', 'entries_already_invoiced');
    }

    public function test_a_task_of_another_company_is_not_found(): void
    {
        $task = $this->makeTask(self::OTHER_COMPANY);

        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/tasks/'.$task->id)->assertNotFound();
        $this->asCompany(self::COMPANY)->putJson('/api/v1/tasks-projects/tasks/'.$task->id, ['name' => 'Mine now'])->assertNotFound();
        $this->asCompany(self::COMPANY)->deleteJson('/api/v1/tasks-projects/tasks/'.$task->id)->assertNotFound();
    }

    public function test_every_action_refuses_without_its_ability(): void
    {
        $task = $this->makeTask(self::COMPANY);

        $this->authorization->deny(
            Authorizes::id(Abilities::VIEW_TASK),
            Authorizes::id(Abilities::CREATE_TASK),
            Authorizes::id(Abilities::EDIT_TASK),
            Authorizes::id(Abilities::DELETE_TASK),
        );

        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/tasks')->assertForbidden();
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks', ['name' => 'Nope'])->assertForbidden();
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/tasks/'.$task->id)->assertForbidden();
        $this->asCompany(self::COMPANY)->putJson('/api/v1/tasks-projects/tasks/'.$task->id, ['name' => 'Nope'])->assertForbidden();
        $this->asCompany(self::COMPANY)->deleteJson('/api/v1/tasks-projects/tasks/'.$task->id)->assertForbidden();
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$task->id.'/move', [
            'task_status_id' => $task->task_status_id,
        ])->assertForbidden();
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/board')->assertForbidden();
    }

    public function test_a_task_carries_the_time_logged_against_it(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['duration_minutes' => 60, 'amount' => 10000]);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['duration_minutes' => 30, 'billable' => false, 'amount' => 0]);
        $this->makeEntry(self::COMPANY, (int) $task->id, [
            'duration_minutes' => 45,
            'amount' => 7500,
            'invoice_id' => 77,
            'invoice_item_id' => 88,
        ]);
        $running = $this->makeEntry(self::COMPANY, (int) $task->id, [
            'running_user_id' => self::DEFAULT_USER,
            'ended_at' => null,
            'duration_minutes' => 0,
            'started_at' => Carbon::parse('2026-09-15 09:00:00'),
        ]);

        $response = $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/tasks/'.$task->id);

        $response->assertOk();
        // The running entry has no duration yet, so it is outside every total.
        $response->assertJsonPath('data.time.logged_minutes', 135);
        $response->assertJsonPath('data.time.billable_minutes', 105);
        $response->assertJsonPath('data.time.unbilled_minutes', 60);
        $response->assertJsonPath('data.time.unbilled_amount', 10000);
        $response->assertJsonPath('data.time.invoiced', 'uninvoiced');
        $response->assertJsonCount(1, 'data.time.running');
        $response->assertJsonPath('data.time.running.0.entry_id', (int) $running->id);
        $response->assertJsonPath('data.time.running.0.user_id', self::DEFAULT_USER);
        $response->assertJsonPath('data.time.running.0.started_at', $running->started_at->toIso8601String());
    }

    public function test_the_time_block_costs_three_reads_however_long_the_list_is(): void
    {
        $status = $this->makeStatus(self::COMPANY);

        foreach (range(1, 5) as $index) {
            $task = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id, 'name' => 'Task '.$index]);
            $this->makeEntry(self::COMPANY, (int) $task->id);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/tasks')->assertOk();

        $reads = array_filter(
            DB::getQueryLog(),
            static fn (array $query): bool => str_contains((string) $query['query'], 'tp_time_entries'),
        );

        DB::disableQueryLog();

        self::assertCount(3, $reads, 'The summary must stay three grouped reads, whatever the page holds.');
    }

    public function test_the_invoiced_state_runs_none_then_uninvoiced_then_invoiced(): void
    {
        $untouched = $this->makeTask(self::COMPANY, ['name' => 'Untouched']);
        $unpaidWork = $this->makeTask(self::COMPANY, ['name' => 'Free']);
        $this->makeEntry(self::COMPANY, (int) $unpaidWork->id, ['billable' => false, 'amount' => 0]);

        $partly = $this->makeTask(self::COMPANY, ['name' => 'Partly']);
        $this->makeEntry(self::COMPANY, (int) $partly->id, ['invoice_id' => 77]);
        $this->makeEntry(self::COMPANY, (int) $partly->id);

        $billed = $this->makeTask(self::COMPANY, ['name' => 'Billed']);
        $this->makeEntry(self::COMPANY, (int) $billed->id, ['invoice_id' => 77]);
        $this->makeEntry(self::COMPANY, (int) $billed->id, ['invoice_id' => 78]);
        $this->makeEntry(self::COMPANY, (int) $billed->id, ['billable' => false, 'amount' => 0]);

        self::assertSame('none', $this->timeOf((int) $untouched->id)['invoiced']);
        self::assertSame('none', $this->timeOf((int) $unpaidWork->id)['invoiced']);
        self::assertSame('uninvoiced', $this->timeOf((int) $partly->id)['invoiced']);
        self::assertSame('invoiced', $this->timeOf((int) $billed->id)['invoiced']);

        // Time nobody may bill still counts as logged time.
        self::assertSame(60, $this->timeOf((int) $unpaidWork->id)['logged_minutes']);
        self::assertSame(0, $this->timeOf((int) $unpaidWork->id)['billable_minutes']);
    }

    public function test_every_task_response_carries_the_time_block(): void
    {
        $status = $this->makeStatus(self::COMPANY);
        $task = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id]);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['duration_minutes' => 90, 'amount' => 15000]);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks')
            ->assertOk()
            ->assertJsonPath('data.0.time.logged_minutes', 90)
            ->assertJsonPath('data.0.time.unbilled_amount', 15000);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/board')
            ->assertOk()
            ->assertJsonPath('data.0.tasks.0.time.logged_minutes', 90);

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/tasks/'.$task->id, ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('data.time.logged_minutes', 90);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks', ['name' => 'Brand new'])
            ->assertCreated()
            ->assertJsonPath('data.time.logged_minutes', 0)
            ->assertJsonPath('data.time.invoiced', 'none')
            ->assertJsonPath('data.time.running', []);
    }

    public function test_a_tasks_time_never_counts_another_companys_entries(): void
    {
        $mine = $this->makeTask(self::COMPANY);
        $theirs = $this->makeTask(self::OTHER_COMPANY);

        $this->makeEntry(self::COMPANY, (int) $mine->id, ['duration_minutes' => 60]);
        $this->makeEntry(self::OTHER_COMPANY, (int) $theirs->id, ['duration_minutes' => 300]);

        self::assertSame(60, $this->timeOf((int) $mine->id)['logged_minutes']);
    }

    public function test_the_invoiced_filter_splits_billed_tasks_from_unbilled_ones(): void
    {
        $status = $this->makeStatus(self::COMPANY);
        $untouched = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id, 'name' => 'Untouched']);

        $unbilled = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id, 'name' => 'Unbilled']);
        $this->makeEntry(self::COMPANY, (int) $unbilled->id);

        $partly = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id, 'name' => 'Partly']);
        $this->makeEntry(self::COMPANY, (int) $partly->id, ['invoice_id' => 77]);
        $this->makeEntry(self::COMPANY, (int) $partly->id);

        $billed = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id, 'name' => 'Billed']);
        $this->makeEntry(self::COMPANY, (int) $billed->id, ['invoice_id' => 77]);

        $this->assertListReturns([$unbilled->id, $partly->id], '?invoiced=0');
        $this->assertListReturns([$billed->id], '?invoiced=1');
        $this->assertListReturns(
            [$untouched->id, $unbilled->id, $partly->id, $billed->id],
            '',
        );
    }

    public function test_a_running_clock_alone_does_not_make_a_task_uninvoiced(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $this->makeEntry(self::COMPANY, (int) $task->id, [
            'running_user_id' => self::DEFAULT_USER,
            'ended_at' => null,
            'duration_minutes' => 0,
        ]);

        self::assertSame('none', $this->timeOf((int) $task->id)['invoiced']);
        $this->assertListReturns([], '?invoiced=0');
    }

    public function test_auto_start_runs_the_creators_clock_on_the_new_task(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'auto_start_tasks', true);

        $response = $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks', ['name' => 'Start me']);

        $response->assertCreated();
        $response->assertJsonCount(1, 'data.time.running');
        $response->assertJsonPath('data.time.running.0.user_id', self::DEFAULT_USER);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/timer')
            ->assertJsonPath('data.task_id', $response->json('data.id'));
    }

    public function test_auto_start_leaves_a_timer_that_is_already_running_alone(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'auto_start_tasks', true);
        $busy = $this->makeTask(self::COMPANY, ['name' => 'Busy']);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/timer/start', ['task_id' => $busy->id])
            ->assertCreated();

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks', ['name' => 'Later'])
            ->assertCreated()
            ->assertJsonPath('data.time.running', []);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/timer')
            ->assertJsonPath('data.task_id', (int) $busy->id);
    }

    public function test_without_the_setting_a_new_task_starts_no_clock(): void
    {
        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks', ['name' => 'Quiet'])
            ->assertCreated()
            ->assertJsonPath('data.time.running', []);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/timer')
            ->assertExactJson(['data' => null]);
    }

    public function test_the_lock_refuses_to_edit_move_or_delete_an_invoiced_task(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'lock_invoiced_tasks', true);

        $status = $this->makeStatus(self::COMPANY);
        $target = $this->makeStatus(self::COMPANY, ['name' => 'Done', 'position' => 2, 'is_default' => false]);
        $task = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id]);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['invoice_id' => 77, 'invoice_item_id' => 88]);

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/tasks/'.$task->id, ['name' => 'Renamed'])
            ->assertStatus(422)
            ->assertJsonPath('error', 'task_locked');

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/'.$task->id.'/move', ['task_status_id' => $target->id])
            ->assertStatus(422)
            ->assertJsonPath('error', 'task_locked');

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/tasks/'.$task->id)
            ->assertStatus(422)
            ->assertJsonPath('error', 'task_locked');

        self::assertSame('Build the landing page', (string) Task::query()->findOrFail($task->id)->name);
    }

    public function test_the_lock_leaves_a_task_that_is_only_partly_invoiced_editable(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'lock_invoiced_tasks', true);

        $task = $this->makeTask(self::COMPANY);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['invoice_id' => 77]);
        $this->makeEntry(self::COMPANY, (int) $task->id);

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/tasks/'.$task->id, ['name' => 'Still moving'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Still moving');
    }

    public function test_an_invoiced_task_is_editable_while_the_lock_is_off(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['invoice_id' => 77]);

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/tasks/'.$task->id, ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed');
    }

    public function test_a_bulk_status_change_moves_what_it_can_and_reports_what_it_cannot(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'lock_invoiced_tasks', true);

        $backlog = $this->makeStatus(self::COMPANY);
        $done = $this->makeStatus(self::COMPANY, ['name' => 'Done', 'position' => 2, 'is_default' => false, 'is_closed' => true]);

        $first = $this->makeTask(self::COMPANY, ['task_status_id' => $backlog->id]);
        $second = $this->makeTask(self::COMPANY, ['task_status_id' => $backlog->id]);
        $locked = $this->makeTask(self::COMPANY, ['task_status_id' => $backlog->id]);
        $this->makeEntry(self::COMPANY, (int) $locked->id, ['invoice_id' => 77]);
        $foreign = $this->makeTask(self::OTHER_COMPANY);

        $response = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/bulk', [
            'action' => 'status',
            'task_status_id' => $done->id,
            'ids' => [$first->id, $second->id, $locked->id, $foreign->id],
        ]);

        $response->assertOk();
        $response->assertExactJson([
            'updated' => [(int) $first->id, (int) $second->id],
            'failed' => [
                ['id' => (int) $locked->id, 'reason' => 'task_locked'],
                ['id' => (int) $foreign->id, 'reason' => 'not_found'],
            ],
        ]);

        self::assertSame((int) $done->id, (int) Task::query()->findOrFail($first->id)->task_status_id);
        self::assertNotNull(Task::query()->findOrFail($second->id)->closed_at);
        self::assertSame((int) $backlog->id, (int) Task::query()->findOrFail($locked->id)->task_status_id);
    }

    public function test_a_bulk_delete_keeps_the_tasks_it_may_not_delete(): void
    {
        $status = $this->makeStatus(self::COMPANY);
        $gone = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id]);
        $invoiced = $this->makeTask(self::COMPANY, ['task_status_id' => $status->id]);
        $this->makeEntry(self::COMPANY, (int) $invoiced->id, ['invoice_id' => 77, 'invoice_item_id' => 88]);

        $response = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/bulk', [
            'action' => 'delete',
            'ids' => [$gone->id, $invoiced->id],
        ]);

        $response->assertOk();
        $response->assertExactJson([
            'updated' => [(int) $gone->id],
            'failed' => [['id' => (int) $invoiced->id, 'reason' => 'entries_already_invoiced']],
        ]);

        self::assertNull(Task::query()->find($gone->id));
        self::assertNotNull(Task::query()->find($invoiced->id));
    }

    public function test_a_bulk_request_is_checked_before_anything_moves(): void
    {
        $task = $this->makeTask(self::COMPANY);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/bulk', ['action' => 'archive', 'ids' => [$task->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['action']);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/bulk', ['action' => 'status', 'ids' => [$task->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['task_status_id']);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/bulk', ['action' => 'delete', 'ids' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ids']);

        // Deleting is not a status change with an extra field attached.
        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/bulk', [
                'action' => 'delete',
                'ids' => [$task->id],
                'task_status_id' => $task->task_status_id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['task_status_id']);

        self::assertNotNull(Task::query()->find($task->id));
    }

    public function test_each_bulk_action_checks_its_own_ability(): void
    {
        $task = $this->makeTask(self::COMPANY);

        $this->authorization->deny(Authorizes::id(Abilities::DELETE_TASK));

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/bulk', ['action' => 'delete', 'ids' => [$task->id]])
            ->assertForbidden();

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/bulk', [
                'action' => 'status',
                'task_status_id' => $task->task_status_id,
                'ids' => [$task->id],
            ])
            ->assertOk();

        $this->authorization->deny(Authorizes::id(Abilities::EDIT_TASK));

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/bulk', [
                'action' => 'status',
                'task_status_id' => $task->task_status_id,
                'ids' => [$task->id],
            ])
            ->assertForbidden();
    }

    /**
     * The time block of one task, as the API answers it.
     *
     * @return array<string, mixed>
     */
    private function timeOf(int $taskId): array
    {
        $response = $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/tasks/'.$taskId);

        $response->assertOk();

        return (array) $response->json('data.time');
    }

    /** @param list<int> $expected */
    private function assertListReturns(array $expected, string $query): void
    {
        $ids = $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks'.$query)
            ->assertOk()
            ->json('data.*.id');

        self::assertSame(array_map(intval(...), $expected), $ids);
    }

    /**
     * Four tasks that differ in every sortable column, created a day apart so
     * `created_at` orders them without relying on the clock.
     *
     * One carries no priority and one no due date, which is what proves a
     * missing value lands last rather than first.
     */
    private function fourTasks(): void
    {
        $status = $this->makeStatus(self::COMPANY);

        $rows = [
            ['2026-09-01 09:00:00', 'zebra', Task::PRIORITY_LOW, '2026-03-01'],
            ['2026-09-02 09:00:00', 'apple', Task::PRIORITY_URGENT, '2026-01-01'],
            ['2026-09-03 09:00:00', 'Mango', Task::PRIORITY_NORMAL, null],
            ['2026-09-04 09:00:00', 'berry', null, '2026-02-01'],
        ];

        foreach ($rows as [$day, $name, $priority, $due]) {
            Carbon::setTestNow($day);
            $this->makeTask(self::COMPANY, [
                'task_status_id' => $status->id,
                'name' => $name,
                'priority' => $priority,
                'due_date' => $due,
            ]);
        }

        Carbon::setTestNow();
    }

    /**
     * The names the list answers with, in the order it answered them.
     *
     * @return list<string>
     */
    private function names(string $query): array
    {
        $response = $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks?'.$query);

        $response->assertOk();

        return array_column((array) $response->json('data'), 'name');
    }
}
