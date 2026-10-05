<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Modules\TasksProjects\Application\Exceptions\EntriesAlreadyInvoiced;
use Modules\TasksProjects\Application\TaskService;
use Modules\TasksProjects\Application\TaskStatusService;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Tests\TestCase;

final class TaskServiceTest extends TestCase
{
    private const COMPANY = 9;

    private TaskService $tasks;

    private TaskStatusService $statuses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statuses = new TaskStatusService;
        $this->tasks = $this->taskService($this->statuses);
    }

    public function test_it_denormalises_the_customer_from_the_project(): void
    {
        $project = $this->makeProject(self::COMPANY, ['customer_id' => 42]);

        $task = $this->tasks->create(self::COMPANY, ['name' => 'Wireframes', 'project_id' => $project->id]);

        self::assertSame(42, $task->customer_id);
        self::assertSame((int) $project->id, $task->project_id);
    }

    public function test_a_standalone_task_carries_its_own_customer(): void
    {
        $task = $this->tasks->create(self::COMPANY, ['name' => 'Ad hoc call', 'customer_id' => 42]);

        self::assertNull($task->project_id);
        self::assertSame(42, $task->customer_id);
    }

    public function test_a_task_on_an_internal_project_has_no_customer(): void
    {
        $project = $this->makeProject(self::COMPANY, ['customer_id' => null]);

        $task = $this->tasks->create(self::COMPANY, ['name' => 'Internal tooling', 'project_id' => $project->id]);

        self::assertNull($task->customer_id);
    }

    public function test_new_tasks_are_numbered_per_company_and_land_in_the_default_status(): void
    {
        $first = $this->tasks->create(self::COMPANY, ['name' => 'One']);
        $second = $this->tasks->create(self::COMPANY, ['name' => 'Two']);
        $otherCompany = $this->tasks->create(10, ['name' => 'Elsewhere']);

        self::assertSame(1, $first->number);
        self::assertSame(2, $second->number);
        self::assertSame(1, $otherCompany->number);
        self::assertSame('Backlog', $this->statuses->findForCompany(self::COMPANY, (int) $first->task_status_id)->name);
    }

    public function test_new_tasks_are_appended_to_their_column(): void
    {
        $first = $this->tasks->create(self::COMPANY, ['name' => 'One']);
        $second = $this->tasks->create(self::COMPANY, ['name' => 'Two']);

        self::assertSame('1024.0000000000', $first->board_position);
        self::assertSame('2048.0000000000', $second->board_position);
    }

    public function test_an_explicit_status_wins_over_the_default(): void
    {
        $this->statuses->ensureDefaults(self::COMPANY);
        $review = $this->statuses->listFor(self::COMPANY)->firstWhere('name', 'Review');

        $task = $this->tasks->create(self::COMPANY, ['name' => 'One', 'task_status_id' => $review->id]);

        self::assertSame((int) $review->id, $task->task_status_id);
        self::assertNull($task->closed_at);
    }

    public function test_moving_into_a_closed_status_stamps_closed_at_and_leaving_clears_it(): void
    {
        Carbon::setTestNow('2026-09-15 11:30:00');
        $this->statuses->ensureDefaults(self::COMPANY);
        $statuses = $this->statuses->listFor(self::COMPANY)->keyBy('name');
        $task = $this->tasks->create(self::COMPANY, ['name' => 'One']);

        $closed = $this->tasks->update(self::COMPANY, (int) $task->id, ['task_status_id' => $statuses['Done']->id]);
        self::assertSame('2026-09-15 11:30:00', $closed->closed_at?->toDateTimeString());

        $reopened = $this->tasks->update(self::COMPANY, (int) $task->id, ['task_status_id' => $statuses['Review']->id]);
        self::assertNull($reopened->closed_at);
    }

    public function test_changing_the_project_rewrites_the_denormalised_customer(): void
    {
        $first = $this->makeProject(self::COMPANY, ['customer_id' => 42]);
        $second = $this->makeProject(self::COMPANY, ['name' => 'Second', 'customer_id' => 43]);
        $task = $this->tasks->create(self::COMPANY, ['name' => 'One', 'project_id' => $first->id]);

        $moved = $this->tasks->update(self::COMPANY, (int) $task->id, ['project_id' => $second->id]);

        self::assertSame(43, $moved->customer_id);
    }

    public function test_move_drops_the_task_between_two_neighbours_of_the_target_column(): void
    {
        $this->statuses->ensureDefaults(self::COMPANY);
        $progress = $this->statuses->listFor(self::COMPANY)->firstWhere('name', 'In Progress');
        $above = $this->tasks->create(self::COMPANY, ['name' => 'Above', 'task_status_id' => $progress->id]);
        $below = $this->tasks->create(self::COMPANY, ['name' => 'Below', 'task_status_id' => $progress->id]);
        $dragged = $this->tasks->create(self::COMPANY, ['name' => 'Dragged']);

        $moved = $this->tasks->move(
            self::COMPANY,
            (int) $dragged->id,
            (int) $progress->id,
            (int) $above->id,
            (int) $below->id,
        );

        self::assertSame((int) $progress->id, $moved->task_status_id);
        self::assertSame('1536.0000000000', $moved->board_position);
    }

    public function test_deleting_a_task_takes_its_uninvoiced_time_with_it(): void
    {
        $task = $this->tasks->create(self::COMPANY, ['name' => 'One']);
        $this->makeEntry(self::COMPANY, (int) $task->id);

        $this->tasks->delete(self::COMPANY, (int) $task->id);

        self::assertSame(0, Task::query()->forCompany(self::COMPANY)->count());
        self::assertSame(0, TimeEntry::query()->forCompany(self::COMPANY)->count());
    }

    public function test_it_refuses_to_delete_a_task_whose_time_is_already_invoiced(): void
    {
        $task = $this->tasks->create(self::COMPANY, ['name' => 'One']);
        $entry = $this->makeEntry(self::COMPANY, (int) $task->id, ['invoice_id' => 77]);

        $this->expectException(EntriesAlreadyInvoiced::class);
        $this->expectExceptionMessage("Time entries {$entry->id} are already on an invoice.");

        $this->tasks->delete(self::COMPANY, (int) $task->id);
    }

    public function test_it_never_reaches_a_task_of_another_company(): void
    {
        $foreign = $this->makeTask(10);

        $this->expectException(ModelNotFoundException::class);

        $this->tasks->findForCompany(self::COMPANY, (int) $foreign->id);
    }

    public function test_it_refuses_a_project_of_another_company(): void
    {
        $foreign = $this->makeProject(10);

        $this->expectException(ModelNotFoundException::class);

        $this->tasks->create(self::COMPANY, ['name' => 'One', 'project_id' => $foreign->id]);
    }

    public function test_listing_filters_by_project_assignee_and_text(): void
    {
        $project = $this->makeProject(self::COMPANY, ['customer_id' => 42]);
        $this->tasks->create(self::COMPANY, ['name' => 'Landing page', 'project_id' => $project->id, 'assignee_id' => 7]);
        $this->tasks->create(self::COMPANY, ['name' => 'Pricing page', 'project_id' => $project->id, 'assignee_id' => 8]);
        $this->tasks->create(self::COMPANY, ['name' => 'Ad hoc call']);

        self::assertSame(2, $this->tasks->listFor(self::COMPANY, ['project_id' => (int) $project->id])->count());
        self::assertSame(1, $this->tasks->listFor(self::COMPANY, ['assignee_id' => 8])->count());
        self::assertSame(['Landing page', 'Pricing page'], $this->tasks->listFor(self::COMPANY, ['search' => 'page'])->pluck('name')->all());
    }
}
