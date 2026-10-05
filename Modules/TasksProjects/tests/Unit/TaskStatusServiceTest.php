<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\TasksProjects\Application\Exceptions\StatusInUse;
use Modules\TasksProjects\Application\TaskStatusService;
use Modules\TasksProjects\Models\TaskStatus;
use Modules\TasksProjects\Tests\TestCase;

final class TaskStatusServiceTest extends TestCase
{
    private const COMPANY = 9;

    private TaskStatusService $statuses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statuses = new TaskStatusService;
    }

    public function test_it_seeds_backlog_in_progress_review_and_done(): void
    {
        $this->statuses->ensureDefaults(self::COMPANY);

        $statuses = $this->statuses->listFor(self::COMPANY);

        self::assertSame(['Backlog', 'In Progress', 'Review', 'Done'], $statuses->pluck('name')->all());
        self::assertSame([1, 2, 3, 4], $statuses->pluck('position')->all());
        self::assertSame([true, false, false, false], $statuses->pluck('is_default')->all());
        self::assertSame([false, false, false, true], $statuses->pluck('is_closed')->all());
    }

    public function test_seeding_twice_leaves_one_set_of_columns(): void
    {
        $this->statuses->ensureDefaults(self::COMPANY);
        $this->statuses->ensureDefaults(self::COMPANY);

        self::assertSame(4, TaskStatus::query()->forCompany(self::COMPANY)->count());
    }

    public function test_it_never_seeds_over_a_company_that_already_arranged_its_board(): void
    {
        $this->makeStatus(self::COMPANY, ['name' => 'Ideas']);

        $this->statuses->ensureDefaults(self::COMPANY);

        self::assertSame(['Ideas'], $this->statuses->listFor(self::COMPANY)->pluck('name')->all());
    }

    public function test_each_company_gets_its_own_board(): void
    {
        $this->statuses->ensureDefaults(self::COMPANY);
        $this->statuses->ensureDefaults(10);

        self::assertSame(4, TaskStatus::query()->forCompany(self::COMPANY)->count());
        self::assertSame(4, TaskStatus::query()->forCompany(10)->count());
    }

    public function test_it_refuses_to_find_a_status_of_another_company(): void
    {
        $foreign = $this->makeStatus(10);

        $this->expectException(ModelNotFoundException::class);

        $this->statuses->findForCompany(self::COMPANY, (int) $foreign->id);
    }

    public function test_only_one_status_carries_the_default_flag(): void
    {
        $this->statuses->ensureDefaults(self::COMPANY);
        $review = $this->statuses->listFor(self::COMPANY)->firstWhere('name', 'Review');

        $this->statuses->update(self::COMPANY, (int) $review->id, ['is_default' => true]);

        self::assertSame(
            ['Review'],
            $this->statuses->listFor(self::COMPANY)->where('is_default', true)->pluck('name')->values()->all(),
        );
    }

    public function test_reorder_applies_the_wanted_order_and_appends_what_was_left_out(): void
    {
        $this->statuses->ensureDefaults(self::COMPANY);
        $ids = $this->statuses->listFor(self::COMPANY)->pluck('id', 'name');

        $this->statuses->reorder(self::COMPANY, [(int) $ids['Done'], (int) $ids['Review']]);

        self::assertSame(
            ['Done', 'Review', 'Backlog', 'In Progress'],
            $this->statuses->listFor(self::COMPANY)->pluck('name')->all(),
        );
    }

    public function test_reorder_refuses_a_status_of_another_company(): void
    {
        $this->statuses->ensureDefaults(self::COMPANY);
        $foreign = $this->makeStatus(10);

        $this->expectException(ModelNotFoundException::class);

        $this->statuses->reorder(self::COMPANY, [(int) $foreign->id]);
    }

    public function test_it_refuses_to_delete_a_status_that_still_holds_tasks(): void
    {
        $this->statuses->ensureDefaults(self::COMPANY);
        $backlog = $this->statuses->listFor(self::COMPANY)->firstWhere('name', 'Backlog');
        $this->makeTask(self::COMPANY, ['task_status_id' => $backlog->id]);

        $this->expectException(StatusInUse::class);
        $this->expectExceptionMessage('still holds 1 task(s)');

        $this->statuses->delete(self::COMPANY, (int) $backlog->id);
    }

    public function test_it_refuses_to_delete_the_last_status(): void
    {
        $only = $this->makeStatus(self::COMPANY);

        $this->expectException(StatusInUse::class);
        $this->expectExceptionMessage('is the last status');

        $this->statuses->delete(self::COMPANY, (int) $only->id);
    }

    public function test_it_refuses_to_delete_the_default_without_another_default(): void
    {
        $this->statuses->ensureDefaults(self::COMPANY);
        $backlog = $this->statuses->listFor(self::COMPANY)->firstWhere('name', 'Backlog');

        $this->expectException(StatusInUse::class);
        $this->expectExceptionMessage('is the default status');

        $this->statuses->delete(self::COMPANY, (int) $backlog->id);
    }

    public function test_an_empty_non_default_status_can_be_deleted(): void
    {
        $this->statuses->ensureDefaults(self::COMPANY);
        $review = $this->statuses->listFor(self::COMPANY)->firstWhere('name', 'Review');

        $this->statuses->delete(self::COMPANY, (int) $review->id);

        self::assertSame(['Backlog', 'In Progress', 'Done'], $this->statuses->listFor(self::COMPANY)->pluck('name')->all());
    }

    public function test_the_default_status_is_where_new_tasks_land(): void
    {
        self::assertSame('Backlog', $this->statuses->defaultFor(self::COMPANY)->name);
    }
}
