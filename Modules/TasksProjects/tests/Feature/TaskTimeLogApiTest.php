<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Feature;

use Illuminate\Support\Carbon;
use Modules\TasksProjects\Application\TimeEntryService;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Support\ModuleSettings;
use Modules\TasksProjects\Tests\TestCase;

/**
 * The time log of one task: what the grid on the task page reads.
 *
 * The totals on the task are open to anyone who may see the task, but the rows
 * behind them follow the timesheet's own visibility rule, so this is where that
 * split is pinned down.
 */
final class TaskTimeLogApiTest extends TestCase
{
    private const COMPANY = 9;

    private const OTHER_COMPANY = 10;

    private const OTHER_USER = 8;

    public function test_the_log_puts_the_running_row_first_and_then_the_newest(): void
    {
        $task = $this->makeTask(self::COMPANY);

        $oldest = $this->makeEntry(self::COMPANY, (int) $task->id, [
            'started_at' => Carbon::parse('2026-09-01 09:00:00'),
            'ended_at' => Carbon::parse('2026-09-01 10:00:00'),
        ]);
        $newest = $this->makeEntry(self::COMPANY, (int) $task->id, [
            'started_at' => Carbon::parse('2026-09-03 09:00:00'),
            'ended_at' => Carbon::parse('2026-09-03 10:00:00'),
        ]);
        $middle = $this->makeEntry(self::COMPANY, (int) $task->id, [
            'started_at' => Carbon::parse('2026-09-02 09:00:00'),
            'ended_at' => Carbon::parse('2026-09-02 10:00:00'),
        ]);
        // A clock started long before any of them still belongs at the top.
        $running = $this->makeEntry(self::COMPANY, (int) $task->id, [
            'started_at' => Carbon::parse('2026-08-01 09:00:00'),
            'ended_at' => null,
            'duration_minutes' => 0,
            'running_user_id' => self::DEFAULT_USER,
        ]);

        $response = $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks/'.$task->id.'/time-log');

        $response->assertOk();
        self::assertSame(
            [(int) $running->id, (int) $newest->id, (int) $middle->id, (int) $oldest->id],
            $response->json('data.*.id'),
        );
        $response->assertJsonPath('data.0.is_running', true);
    }

    public function test_the_log_only_carries_the_entries_of_its_own_task(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $other = $this->makeTask(self::COMPANY, ['name' => 'Something else']);

        $mine = $this->makeEntry(self::COMPANY, (int) $task->id);
        $this->makeEntry(self::COMPANY, (int) $other->id);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks/'.$task->id.'/time-log')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (int) $mine->id);
    }

    public function test_the_log_is_capped_rather_than_paged(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $rows = TimeEntryService::LOG_LIMIT + 3;

        for ($minute = 0; $minute < $rows; $minute++) {
            $this->makeEntry(self::COMPANY, (int) $task->id, [
                'started_at' => Carbon::parse('2026-09-01 00:00:00')->addMinutes($minute),
                'ended_at' => Carbon::parse('2026-09-01 00:30:00')->addMinutes($minute),
                'duration_minutes' => 30,
            ]);
        }

        $response = $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks/'.$task->id.'/time-log');

        $response->assertOk();
        $response->assertJsonCount(TimeEntryService::LOG_LIMIT, 'data');
        self::assertSame($rows, TimeEntry::query()->forCompany(self::COMPANY)->count());
    }

    public function test_without_view_all_time_the_log_is_the_callers_own_rows(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $mine = $this->makeEntry(self::COMPANY, (int) $task->id);
        $theirs = $this->makeEntry(self::COMPANY, (int) $task->id, ['user_id' => self::OTHER_USER]);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks/'.$task->id.'/time-log')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->authorization->deny(Authorizes::id(Abilities::VIEW_ALL_TIME));

        $response = $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks/'.$task->id.'/time-log');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', (int) $mine->id);

        // The totals on the task still count everybody's time.
        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks/'.$task->id)
            ->assertJsonPath('data.time.logged_minutes', 120);

        self::assertNotNull($theirs->id);
    }

    public function test_the_company_setting_opens_the_log_without_the_ability(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $this->makeEntry(self::COMPANY, (int) $task->id);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['user_id' => self::OTHER_USER]);

        $this->authorization->deny(Authorizes::id(Abilities::VIEW_ALL_TIME));
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'members_see_all_time', true);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks/'.$task->id.'/time-log')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_the_log_of_another_companys_task_is_not_found(): void
    {
        $task = $this->makeTask(self::OTHER_COMPANY);
        $this->makeEntry(self::OTHER_COMPANY, (int) $task->id);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks/'.$task->id.'/time-log')
            ->assertNotFound();
    }

    public function test_reading_the_log_needs_the_task_view_ability(): void
    {
        $task = $this->makeTask(self::COMPANY);

        $this->authorization->deny(Authorizes::id(Abilities::VIEW_TASK));

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks/'.$task->id.'/time-log')
            ->assertForbidden();
    }
}
