<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Feature;

use Modules\TasksProjects\Application\Rounding;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Support\ModuleSettings;
use Modules\TasksProjects\Tests\TestCase;

final class TimeEntriesApiTest extends TestCase
{
    private const COMPANY = 9;

    private const OTHER_COMPANY = 10;

    private const OTHER_USER = 8;

    public function test_a_start_and_an_end_become_minutes_and_money(): void
    {
        $task = $this->taskOnProjectAt(6000);

        $response = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/time-entries', [
            'task_id' => $task,
            'started_at' => '2026-09-15 09:00:00',
            'ended_at' => '2026-09-15 10:30:00',
            'description' => 'Pairing on the importer',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.user_id', self::DEFAULT_USER);
        $response->assertJsonPath('data.duration_minutes', 90);
        $response->assertJsonPath('data.rate', 6000);
        $response->assertJsonPath('data.amount', 9000);
        $response->assertJsonPath('data.is_running', false);
        $response->assertJsonPath('data.description', 'Pairing on the importer');
    }

    public function test_the_company_rounding_increment_is_applied_when_the_entry_is_saved(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_minutes', 15);
        $task = $this->taskOnProjectAt(6000);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/time-entries', [
                'task_id' => $task,
                'started_at' => '2026-09-15 09:00:00',
                'ended_at' => '2026-09-15 09:20:00',
            ])
            ->assertCreated()
            ->assertJsonPath('data.duration_minutes', 15)
            ->assertJsonPath('data.amount', 1500);
    }

    public function test_the_rate_is_frozen_at_save_and_survives_a_later_project_rate_change(): void
    {
        $project = $this->makeProject(self::COMPANY, ['customer_id' => 42, 'default_rate' => 6000]);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);

        $entry = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/time-entries', [
            'task_id' => $task->id,
            'duration_minutes' => 60,
        ])->assertCreated()->json('data.id');

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/projects/'.$project->id, ['default_rate' => 9000])
            ->assertOk();

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries/'.$entry)
            ->assertOk()
            ->assertJsonPath('data.rate', 6000)
            ->assertJsonPath('data.amount', 6000);
    }

    public function test_the_list_filters_and_never_shows_a_running_entry(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $this->makeEntry(self::COMPANY, $task, ['duration_minutes' => 30]);
        $this->makeEntry(self::COMPANY, $task, ['billable' => false, 'duration_minutes' => 45]);
        $this->makeEntry(self::COMPANY, $task, ['invoice_id' => 77, 'invoice_item_id' => 88]);
        $this->makeEntry(self::COMPANY, $task, ['running_user_id' => self::DEFAULT_USER, 'ended_at' => null]);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries?billed=1')
            ->assertJsonPath('meta.total', 1);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries?billed=0')
            ->assertJsonPath('meta.total', 2);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries?billable=0')
            ->assertJsonPath('meta.total', 1);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries?task_id='.$task.'&from=2026-09-01&to=2026-09-01')
            ->assertJsonPath('meta.total', 3);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries?from=2026-09-02')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_without_view_all_time_the_list_is_the_callers_own_rows(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $this->makeEntry(self::COMPANY, $task);
        $this->makeEntry(self::COMPANY, $task, ['user_id' => self::OTHER_USER]);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries')
            ->assertJsonPath('meta.total', 2);

        $this->authorization->deny(Authorizes::id(Abilities::VIEW_ALL_TIME));

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries?user_id='.self::OTHER_USER)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.user_id', self::DEFAULT_USER);
    }

    public function test_the_company_setting_opens_the_timesheet_without_the_ability(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $this->makeEntry(self::COMPANY, $task);
        $this->makeEntry(self::COMPANY, $task, ['user_id' => self::OTHER_USER]);

        $this->authorization->deny(Authorizes::id(Abilities::VIEW_ALL_TIME));
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'members_see_all_time', true);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_another_members_entry_is_invisible_without_the_ability_or_the_setting(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $entry = $this->makeEntry(self::COMPANY, $task, ['user_id' => self::OTHER_USER]);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries/'.$entry->id)
            ->assertOk();

        $this->authorization->deny(Authorizes::id(Abilities::VIEW_ALL_TIME));

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/time-entries/'.$entry->id)
            ->assertForbidden();
    }

    public function test_writing_over_another_members_entry_needs_edit_all_time(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $mine = $this->makeEntry(self::COMPANY, $task);
        $theirs = $this->makeEntry(self::COMPANY, $task, ['user_id' => self::OTHER_USER]);

        $this->authorization->deny(Authorizes::id(Abilities::EDIT_ALL_TIME));

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/time-entries/'.$mine->id, ['duration_minutes' => 30])
            ->assertOk()
            ->assertJsonPath('data.duration_minutes', 30);

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/time-entries/'.$theirs->id, ['duration_minutes' => 30])
            ->assertForbidden();

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/time-entries/'.$theirs->id)
            ->assertForbidden();

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/time-entries', [
                'task_id' => $task,
                'user_id' => self::OTHER_USER,
                'duration_minutes' => 30,
            ])
            ->assertForbidden();
    }

    public function test_logging_time_for_someone_else_is_allowed_with_edit_all_time(): void
    {
        $task = $this->taskOnProjectAt(6000);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/time-entries', [
                'task_id' => $task,
                'user_id' => self::OTHER_USER,
                'duration_minutes' => 60,
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', self::OTHER_USER);
    }

    public function test_an_invoiced_entry_refuses_a_change_to_its_time_or_its_task(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $elsewhere = (int) $this->makeTask(self::COMPANY, ['name' => 'Another task'])->id;
        $entry = $this->makeEntry(self::COMPANY, $task, ['invoice_id' => 77, 'invoice_item_id' => 88]);

        $refused = [
            ['duration_minutes' => 240],
            ['started_at' => '2026-09-01 08:00:00'],
            ['ended_at' => '2026-09-01 12:00:00'],
            ['billable' => false],
            ['task_id' => $elsewhere],
        ];

        foreach ($refused as $payload) {
            $this->asCompany(self::COMPANY)
                ->putJson('/api/v1/tasks-projects/time-entries/'.$entry->id, $payload)
                ->assertStatus(422)
                ->assertJsonPath('error', 'entries_already_invoiced');
        }

        $stored = TimeEntry::query()->findOrFail($entry->id);
        self::assertSame(60, (int) $stored->duration_minutes);
        self::assertSame(10000, (int) $stored->amount);
        self::assertTrue((bool) $stored->billable);
        self::assertSame($task, (int) $stored->task_id);
    }

    public function test_an_invoiced_entry_still_takes_a_new_description(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $entry = $this->makeEntry(self::COMPANY, $task, ['invoice_id' => 77, 'invoice_item_id' => 88]);

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/time-entries/'.$entry->id, ['description' => 'Typo fix'])
            ->assertOk()
            ->assertJsonPath('data.description', 'Typo fix')
            ->assertJsonPath('data.duration_minutes', 60)
            ->assertJsonPath('data.amount', 10000);
    }

    public function test_an_invoiced_entry_accepts_a_form_that_posts_its_own_values_back(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $entry = $this->makeEntry(self::COMPANY, $task, ['invoice_id' => 77, 'invoice_item_id' => 88]);

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/time-entries/'.$entry->id, [
                'task_id' => $task,
                'started_at' => $entry->started_at->toIso8601String(),
                'ended_at' => $entry->ended_at->toIso8601String(),
                'duration_minutes' => 60,
                'billable' => true,
                'description' => 'Same row, new note',
            ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Same row, new note');
    }

    public function test_the_company_rounding_direction_is_applied_when_the_entry_is_saved(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_minutes', 15);
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_direction', Rounding::DOWN);
        $task = $this->taskOnProjectAt(6000);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/time-entries', [
                'task_id' => $task,
                'started_at' => '2026-09-15 09:00:00',
                'ended_at' => '2026-09-15 09:20:00',
            ])
            ->assertCreated()
            ->assertJsonPath('data.duration_minutes', 15)
            ->assertJsonPath('data.amount', 1500);

        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_direction', Rounding::UP);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/time-entries', [
                'task_id' => $task,
                'started_at' => '2026-09-15 09:00:00',
                'ended_at' => '2026-09-15 09:20:00',
            ])
            ->assertCreated()
            ->assertJsonPath('data.duration_minutes', 30)
            ->assertJsonPath('data.amount', 3000);
    }

    public function test_an_invoiced_entry_cannot_be_deleted(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $entry = $this->makeEntry(self::COMPANY, $task, ['invoice_id' => 77, 'invoice_item_id' => 88]);

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/time-entries/'.$entry->id)
            ->assertStatus(422)
            ->assertJsonPath('error', 'entries_already_invoiced');

        self::assertNotNull(TimeEntry::query()->find($entry->id));
    }

    public function test_an_uninvoiced_entry_is_deleted(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $entry = $this->makeEntry(self::COMPANY, $task);

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/time-entries/'.$entry->id)
            ->assertOk();

        self::assertNull(TimeEntry::query()->find($entry->id));
    }

    public function test_an_entry_of_another_company_is_not_found(): void
    {
        $task = $this->makeTask(self::OTHER_COMPANY);
        $entry = $this->makeEntry(self::OTHER_COMPANY, (int) $task->id);

        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/time-entries/'.$entry->id)->assertNotFound();
        $this->asCompany(self::COMPANY)->putJson('/api/v1/tasks-projects/time-entries/'.$entry->id, ['duration_minutes' => 1])->assertNotFound();
        $this->asCompany(self::COMPANY)->deleteJson('/api/v1/tasks-projects/time-entries/'.$entry->id)->assertNotFound();
    }

    public function test_everything_here_needs_view_own_time(): void
    {
        $task = $this->taskOnProjectAt(6000);
        $entry = $this->makeEntry(self::COMPANY, $task);

        $this->authorization->deny(Authorizes::id(Abilities::VIEW_OWN_TIME));

        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/time-entries')->assertForbidden();
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/time-entries', ['task_id' => $task])->assertForbidden();
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/time-entries/'.$entry->id)->assertForbidden();
        $this->asCompany(self::COMPANY)->putJson('/api/v1/tasks-projects/time-entries/'.$entry->id, ['duration_minutes' => 1])->assertForbidden();
        $this->asCompany(self::COMPANY)->deleteJson('/api/v1/tasks-projects/time-entries/'.$entry->id)->assertForbidden();
    }

    private function taskOnProjectAt(int $rate): int
    {
        $project = $this->makeProject(self::COMPANY, ['customer_id' => 42, 'default_rate' => $rate, 'currency_id' => 3]);

        return (int) $this->makeTask(self::COMPANY, ['project_id' => $project->id])->id;
    }
}
