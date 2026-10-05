<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Feature;

use Illuminate\Support\Carbon;
use Modules\TasksProjects\Application\Rounding;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Support\ModuleSettings;
use Modules\TasksProjects\Tests\TestCase;

final class TimerApiTest extends TestCase
{
    private const COMPANY = 9;

    private const OTHER_COMPANY = 10;

    public function test_the_timer_is_null_until_it_is_started(): void
    {
        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/timer')
            ->assertOk()
            ->assertExactJson(['data' => null]);
    }

    public function test_starting_opens_an_entry_that_carries_no_money_yet(): void
    {
        Carbon::setTestNow('2026-09-15 09:00:00');
        $task = $this->taskOnProjectAt(6000);

        $response = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/start', [
            'task_id' => $task,
            'description' => 'Fixing the importer',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.is_running', true);
        $response->assertJsonPath('data.user_id', self::DEFAULT_USER);
        $response->assertJsonPath('data.duration_minutes', 0);
        $response->assertJsonPath('data.amount', 0);
        $response->assertJsonPath('data.ended_at', null);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/timer')
            ->assertOk()
            ->assertJsonPath('data.description', 'Fixing the importer');
    }

    public function test_a_start_on_another_task_is_a_conflict(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $other = (int) $this->makeTask(self::COMPANY, ['name' => 'Something else'])->id;
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/start', ['task_id' => $task])->assertCreated();

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/timer/start', ['task_id' => $other])
            ->assertStatus(409)
            ->assertJsonPath('error', 'timer_already_running');
    }

    public function test_starting_the_task_already_on_the_clock_updates_it_rather_than_conflicting(): void
    {
        Carbon::setTestNow('2026-09-15 09:00:00');
        $task = $this->taskOnProjectAt(6000);

        $started = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/start', [
            'task_id' => $task,
            'description' => 'Reading the ticket',
        ]);
        $started->assertCreated();

        // The clock keeps running from where it was; only the details change.
        Carbon::setTestNow('2026-09-15 09:20:00');
        $again = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/start', [
            'task_id' => $task,
            'description' => 'Fixing the importer',
            'billable' => false,
        ]);

        $again->assertOk();
        $again->assertJsonPath('data.id', $started->json('data.id'));
        $again->assertJsonPath('data.is_running', true);
        $again->assertJsonPath('data.description', 'Fixing the importer');
        $again->assertJsonPath('data.billable', false);
        $again->assertJsonPath('data.started_at', $started->json('data.started_at'));

        self::assertSame(1, TimeEntry::query()->forCompany(self::COMPANY)->count());
    }

    public function test_a_start_can_override_the_tasks_billable_flag(): void
    {
        $task = $this->taskOnProjectAt(6000);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/timer/start', ['task_id' => $task, 'billable' => false])
            ->assertCreated()
            ->assertJsonPath('data.billable', false);
    }

    public function test_stopping_applies_the_description_and_the_billable_flag_it_carries(): void
    {
        $task = $this->taskOnProjectAt(6000);

        Carbon::setTestNow('2026-09-15 09:00:00');
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/start', [
            'task_id' => $task,
            'description' => 'Reading the ticket',
        ])->assertCreated();

        Carbon::setTestNow('2026-09-15 10:00:00');
        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/timer/stop', [
                'description' => 'Fixed the importer',
                'billable' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Fixed the importer')
            ->assertJsonPath('data.billable', false)
            ->assertJsonPath('data.duration_minutes', 60)
            // Non-billable time is still rated; what it is worth is the
            // invoice's question, not the timesheet's.
            ->assertJsonPath('data.amount', 6000);
    }

    public function test_stopping_without_details_keeps_what_the_start_recorded(): void
    {
        $task = $this->taskOnProjectAt(6000);

        Carbon::setTestNow('2026-09-15 09:00:00');
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/start', [
            'task_id' => $task,
            'description' => 'Reading the ticket',
        ])->assertCreated();

        Carbon::setTestNow('2026-09-15 10:00:00');
        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/timer/stop')
            ->assertOk()
            ->assertJsonPath('data.description', 'Reading the ticket')
            ->assertJsonPath('data.billable', true);
    }

    public function test_stopping_rounds_the_elapsed_time_and_freezes_the_rate(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_minutes', 15);
        $task = $this->taskOnProjectAt(6000);

        Carbon::setTestNow('2026-09-15 09:00:00');
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/start', ['task_id' => $task])->assertCreated();

        Carbon::setTestNow('2026-09-15 09:50:00');
        $response = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/stop');

        $response->assertOk();
        $response->assertJsonPath('data.is_running', false);
        $response->assertJsonPath('data.duration_minutes', 45);
        $response->assertJsonPath('data.rate', 6000);
        $response->assertJsonPath('data.amount', 4500);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/timer')
            ->assertExactJson(['data' => null]);
    }

    public function test_stopping_follows_the_companys_rounding_direction(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_minutes', 15);
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_direction', Rounding::UP);
        $task = $this->taskOnProjectAt(6000);

        Carbon::setTestNow('2026-09-15 09:00:00');
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/start', ['task_id' => $task])->assertCreated();

        Carbon::setTestNow('2026-09-15 09:50:00');

        // Nearest would have billed 45 minutes; rounding up takes the hour.
        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/timer/stop')
            ->assertOk()
            ->assertJsonPath('data.duration_minutes', 60)
            ->assertJsonPath('data.amount', 6000);
    }

    public function test_the_stopped_entry_joins_the_timesheet(): void
    {
        $task = $this->taskOnProjectAt(6000);

        Carbon::setTestNow('2026-09-15 09:00:00');
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/start', ['task_id' => $task]);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries')
            ->assertJsonPath('meta.total', 0);

        Carbon::setTestNow('2026-09-15 10:00:00');
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/stop')->assertOk();

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.duration_minutes', 60);
    }

    public function test_discarding_throws_the_running_entry_away(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/start', ['task_id' => $task])->assertCreated();

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/timer')
            ->assertOk()
            ->assertJson(['success' => true]);

        self::assertSame(0, TimeEntry::query()->forCompany(self::COMPANY)->count());
    }

    public function test_stopping_or_discarding_an_idle_timer_is_not_found(): void
    {
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/stop')->assertNotFound();
        $this->asCompany(self::COMPANY)->deleteJson('/api/v1/tasks-projects/timer')->assertNotFound();
    }

    public function test_a_task_of_another_company_cannot_be_timed(): void
    {
        $task = $this->makeTask(self::OTHER_COMPANY);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/timer/start', ['task_id' => $task->id])
            ->assertNotFound();
    }

    public function test_the_running_timer_belongs_to_one_company_at_a_time(): void
    {
        $here = $this->taskOnProjectAt(6000);
        $there = (int) $this->makeTask(self::OTHER_COMPANY)->id;

        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/start', ['task_id' => $here])->assertCreated();

        $this->asCompany(self::OTHER_COMPANY)
            ->getJson('/api/v1/tasks-projects/timer')
            ->assertExactJson(['data' => null]);

        $this->asCompany(self::OTHER_COMPANY)
            ->postJson('/api/v1/tasks-projects/timer/start', ['task_id' => $there])
            ->assertCreated();

        self::assertSame(1, TimeEntry::query()->forCompany(self::COMPANY)->whereNotNull('running_user_id')->count());
        self::assertSame(1, TimeEntry::query()->forCompany(self::OTHER_COMPANY)->whereNotNull('running_user_id')->count());
    }

    public function test_the_timer_needs_the_own_time_ability(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $this->authorization->deny(Authorizes::id(Abilities::VIEW_OWN_TIME));

        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/timer')->assertForbidden();
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/start', ['task_id' => $task])->assertForbidden();
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/timer/stop')->assertForbidden();
        $this->asCompany(self::COMPANY)->deleteJson('/api/v1/tasks-projects/timer')->assertForbidden();
    }

    public function test_a_task_row_starts_the_clock_on_that_task(): void
    {
        Carbon::setTestNow('2026-09-15 09:00:00');
        $task = $this->taskOnProjectAt(6000);

        $response = $this->asCompany(self::COMPANY)->postJson(
            '/api/v1/tasks-projects/tasks/'.$task.'/start',
            ['description' => 'Fixing the importer'],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.task_id', $task);
        $response->assertJsonPath('data.user_id', self::DEFAULT_USER);
        $response->assertJsonPath('data.is_running', true);
        $response->assertJsonPath('data.description', 'Fixing the importer');

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks/'.$task)
            ->assertJsonPath('data.time.running.0.user_id', self::DEFAULT_USER);
    }

    public function test_a_second_task_start_is_the_same_conflict_the_timer_reports(): void
    {
        $first = $this->taskOnProjectAt(6000);
        $second = (int) $this->makeTask(self::COMPANY, ['name' => 'Something else'])->id;

        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$first.'/start')->assertCreated();

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/'.$second.'/start')
            ->assertStatus(409)
            ->assertJsonPath('error', 'timer_already_running');

        self::assertSame(1, TimeEntry::query()->forCompany(self::COMPANY)->whereNotNull('running_user_id')->count());
    }

    public function test_stopping_a_task_closes_the_clock_that_runs_on_it(): void
    {
        $task = $this->taskOnProjectAt(6000);

        Carbon::setTestNow('2026-09-15 09:00:00');
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$task.'/start')->assertCreated();

        Carbon::setTestNow('2026-09-15 10:00:00');
        $response = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$task.'/stop');

        $response->assertOk();
        $response->assertJsonPath('data.is_running', false);
        $response->assertJsonPath('data.duration_minutes', 60);
        $response->assertJsonPath('data.amount', 6000);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/tasks/'.$task)
            ->assertJsonPath('data.time.logged_minutes', 60)
            ->assertJsonPath('data.time.unbilled_amount', 6000)
            ->assertJsonPath('data.time.running', []);
    }

    public function test_a_task_start_on_the_running_task_applies_the_details_it_carries(): void
    {
        Carbon::setTestNow('2026-09-15 09:00:00');
        $task = $this->taskOnProjectAt(6000);

        $started = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$task.'/start');
        $started->assertCreated();

        $again = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$task.'/start', [
            'description' => 'Carried on with it',
            'billable' => false,
        ]);

        $again->assertOk();
        $again->assertJsonPath('data.id', $started->json('data.id'));
        $again->assertJsonPath('data.description', 'Carried on with it');
        $again->assertJsonPath('data.billable', false);
    }

    public function test_stopping_a_task_carries_the_description_and_the_billable_flag(): void
    {
        $task = $this->taskOnProjectAt(6000);

        Carbon::setTestNow('2026-09-15 09:00:00');
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$task.'/start')->assertCreated();

        Carbon::setTestNow('2026-09-15 10:00:00');
        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/'.$task.'/stop', [
                'description' => 'Wrote the importer test',
                'billable' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Wrote the importer test')
            ->assertJsonPath('data.billable', false)
            ->assertJsonPath('data.duration_minutes', 60);
    }

    public function test_a_mismatched_stop_is_refused_before_it_writes_anything(): void
    {
        $running = $this->taskOnProjectAt(6000);
        $idle = (int) $this->makeTask(self::COMPANY, ['name' => 'Idle'])->id;

        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$running.'/start', [
            'description' => 'Reading the ticket',
        ])->assertCreated();

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/'.$idle.'/stop', [
                'description' => 'Should never land',
                'billable' => false,
            ])
            ->assertStatus(409)
            ->assertJsonPath('error', 'timer_mismatch');

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/timer')
            ->assertJsonPath('data.task_id', $running)
            ->assertJsonPath('data.description', 'Reading the ticket')
            ->assertJsonPath('data.billable', true);
    }

    public function test_stopping_the_wrong_task_is_a_mismatch_rather_than_a_stop(): void
    {
        $running = $this->taskOnProjectAt(6000);
        $idle = (int) $this->makeTask(self::COMPANY, ['name' => 'Idle'])->id;

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/'.$idle.'/stop')
            ->assertStatus(409)
            ->assertJsonPath('error', 'timer_mismatch');

        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$running.'/start')->assertCreated();

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/'.$idle.'/stop')
            ->assertStatus(409)
            ->assertJsonPath('error', 'timer_mismatch');

        // The clock the caller really had running is untouched.
        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/timer')
            ->assertJsonPath('data.task_id', $running);
    }

    public function test_the_task_routes_stay_inside_the_company(): void
    {
        $theirs = (int) $this->makeTask(self::OTHER_COMPANY)->id;

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/'.$theirs.'/start')
            ->assertNotFound();

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/tasks/'.$theirs.'/stop')
            ->assertNotFound();
    }

    public function test_the_task_routes_need_both_the_task_and_the_own_time_ability(): void
    {
        $task = $this->taskOnProjectAt(6000);

        $this->authorization->deny(Authorizes::id(Abilities::VIEW_TASK));

        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$task.'/start')->assertForbidden();
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$task.'/stop')->assertForbidden();

        $this->authorization->denied = [Authorizes::id(Abilities::VIEW_OWN_TIME)];

        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$task.'/start')->assertForbidden();
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/tasks/'.$task.'/stop')->assertForbidden();
    }

    private function taskOnProjectAt(int $rate): int
    {
        $project = $this->makeProject(self::COMPANY, ['customer_id' => 42, 'default_rate' => $rate, 'currency_id' => 3]);

        return (int) $this->makeTask(self::COMPANY, ['project_id' => $project->id])->id;
    }
}
