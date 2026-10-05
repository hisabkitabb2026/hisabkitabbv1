<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Feature;

use Modules\TasksProjects\Application\TaskStatusService;
use Modules\TasksProjects\Models\TaskStatus;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Tests\TestCase;

final class TaskStatusesApiTest extends TestCase
{
    private const COMPANY = 9;

    private const OTHER_COMPANY = 10;

    public function test_the_four_defaults_appear_the_first_time_a_company_asks(): void
    {
        $response = $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/task-statuses');

        $response->assertOk();
        $response->assertJsonCount(4, 'data');
        $response->assertJsonPath('data.0.name', 'Backlog');
        $response->assertJsonPath('data.0.is_default', true);
        $response->assertJsonPath('data.3.name', 'Done');
        $response->assertJsonPath('data.3.is_closed', true);
        $response->assertJsonPath('data.0.position', 1);
        $response->assertJsonPath('data.3.position', 4);
    }

    public function test_asking_twice_does_not_seed_the_defaults_again(): void
    {
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/task-statuses')->assertOk();
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/task-statuses')->assertJsonCount(4, 'data');

        self::assertSame(count(TaskStatusService::DEFAULTS), TaskStatus::query()->forCompany(self::COMPANY)->count());
    }

    public function test_two_companies_get_their_own_independent_columns(): void
    {
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/task-statuses')->assertJsonCount(4, 'data');
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/task-statuses', ['name' => 'Blocked'])->assertCreated();

        $this->asCompany(self::OTHER_COMPANY)
            ->getJson('/api/v1/tasks-projects/task-statuses')
            ->assertJsonCount(4, 'data')
            ->assertJsonMissing(['name' => 'Blocked']);

        self::assertSame(5, TaskStatus::query()->forCompany(self::COMPANY)->count());
        self::assertSame(4, TaskStatus::query()->forCompany(self::OTHER_COMPANY)->count());
    }

    public function test_a_created_status_lands_last_and_can_take_the_default_over(): void
    {
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/task-statuses');

        $response = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/task-statuses', [
            'name' => 'Blocked',
            'colour' => '#ef4444',
            'is_default' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.position', 5);
        $response->assertJsonPath('data.is_default', true);

        $defaults = TaskStatus::query()->forCompany(self::COMPANY)->where('is_default', true)->pluck('name')->all();
        self::assertSame(['Blocked'], $defaults);
    }

    public function test_only_one_status_stays_the_default_after_an_update(): void
    {
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/task-statuses');
        $review = TaskStatus::query()->forCompany(self::COMPANY)->where('name', 'Review')->firstOrFail();

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/task-statuses/'.$review->id, ['is_default' => true])
            ->assertOk()
            ->assertJsonPath('data.is_default', true);

        self::assertSame(
            ['Review'],
            TaskStatus::query()->forCompany(self::COMPANY)->where('is_default', true)->pluck('name')->all(),
        );
    }

    public function test_reorder_applies_the_wanted_order_and_returns_the_new_board(): void
    {
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/task-statuses');
        $statuses = TaskStatus::query()->forCompany(self::COMPANY)->orderBy('position')->pluck('id', 'name')->all();

        $response = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/task-statuses/reorder', [
            'ids' => [$statuses['Done'], $statuses['Review']],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Done');
        $response->assertJsonPath('data.0.position', 1);
        $response->assertJsonPath('data.1.name', 'Review');
        $response->assertJsonPath('data.2.name', 'Backlog');
        $response->assertJsonPath('data.3.name', 'In Progress');
    }

    public function test_reorder_refuses_an_id_from_another_company(): void
    {
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/task-statuses');
        $foreign = $this->makeStatus(self::OTHER_COMPANY);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/task-statuses/reorder', ['ids' => [$foreign->id]])
            ->assertNotFound();
    }

    public function test_a_status_holding_tasks_cannot_be_deleted(): void
    {
        $status = $this->makeStatus(self::COMPANY, ['name' => 'Backlog']);
        $this->makeStatus(self::COMPANY, ['name' => 'Done', 'position' => 2, 'is_default' => false, 'is_closed' => true]);
        $this->makeTask(self::COMPANY, ['task_status_id' => $status->id]);

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/task-statuses/'.$status->id)
            ->assertStatus(422)
            ->assertJsonPath('error', 'status_in_use');
    }

    public function test_the_last_status_of_a_company_cannot_be_deleted(): void
    {
        $status = $this->makeStatus(self::COMPANY);

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/task-statuses/'.$status->id)
            ->assertStatus(422)
            ->assertJsonPath('error', 'status_in_use');
    }

    public function test_an_empty_status_is_deleted(): void
    {
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/task-statuses');
        $review = TaskStatus::query()->forCompany(self::COMPANY)->where('name', 'Review')->firstOrFail();

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/task-statuses/'.$review->id)
            ->assertOk()
            ->assertJson(['success' => true]);

        self::assertSame(3, TaskStatus::query()->forCompany(self::COMPANY)->count());
    }

    public function test_a_status_of_another_company_is_not_found(): void
    {
        $foreign = $this->makeStatus(self::OTHER_COMPANY);

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/task-statuses/'.$foreign->id, ['name' => 'Mine now'])
            ->assertNotFound();
    }

    public function test_reading_needs_view_task_and_writing_needs_manage_task_status(): void
    {
        $status = $this->makeStatus(self::COMPANY);

        $this->authorization->deny(Authorizes::id(Abilities::MANAGE_TASK_STATUS));

        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/task-statuses')->assertOk();
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/task-statuses', ['name' => 'Blocked'])->assertForbidden();
        $this->asCompany(self::COMPANY)->putJson('/api/v1/tasks-projects/task-statuses/'.$status->id, ['name' => 'Nope'])->assertForbidden();
        $this->asCompany(self::COMPANY)->deleteJson('/api/v1/tasks-projects/task-statuses/'.$status->id)->assertForbidden();
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/task-statuses/reorder', ['ids' => [$status->id]])->assertForbidden();

        $this->authorization->deny(Authorizes::id(Abilities::VIEW_TASK));
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/task-statuses')->assertForbidden();
    }
}
