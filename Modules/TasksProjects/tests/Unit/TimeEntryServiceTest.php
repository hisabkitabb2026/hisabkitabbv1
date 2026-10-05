<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\TasksProjects\Application\Exceptions\EntriesAlreadyInvoiced;
use Modules\TasksProjects\Application\RateResolver;
use Modules\TasksProjects\Application\TimeEntryService;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\ModuleSettings;
use Modules\TasksProjects\Tests\TestCase;

final class TimeEntryServiceTest extends TestCase
{
    private const COMPANY = 9;

    private const USER = 7;

    private TimeEntryService $entries;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entries = new TimeEntryService(
            new RateResolver,
            $this->moduleSettings(),
            $this->taskService(),
        );
    }

    public function test_a_start_and_an_end_become_minutes(): void
    {
        $task = $this->makeTask(self::COMPANY);

        $entry = $this->entries->create(self::COMPANY, [
            'task_id' => $task->id,
            'user_id' => self::USER,
            'started_at' => '2026-09-15 09:00:00',
            'ended_at' => '2026-09-15 10:30:00',
        ]);

        self::assertSame(90, $entry->duration_minutes);
    }

    public function test_the_company_increment_is_applied_when_the_entry_is_saved(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_minutes', 15);
        $task = $this->makeTask(self::COMPANY);

        $entry = $this->entries->create(self::COMPANY, [
            'task_id' => $task->id,
            'user_id' => self::USER,
            'started_at' => '2026-09-15 09:00:00',
            'ended_at' => '2026-09-15 09:50:00',
        ]);

        self::assertSame(45, $entry->duration_minutes);
    }

    public function test_a_plain_duration_is_taken_as_typed(): void
    {
        $task = $this->makeTask(self::COMPANY);

        $entry = $this->entries->create(self::COMPANY, [
            'task_id' => $task->id,
            'user_id' => self::USER,
            'duration_minutes' => 25,
        ]);

        self::assertSame(25, $entry->duration_minutes);
        self::assertNull($entry->ended_at);
    }

    public function test_the_resolved_rate_and_the_cached_amount_are_written_onto_the_entry(): void
    {
        $project = $this->makeProject(self::COMPANY, ['default_rate' => 6000, 'currency_id' => 3]);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);

        $entry = $this->entries->create(self::COMPANY, [
            'task_id' => $task->id,
            'user_id' => self::USER,
            'duration_minutes' => 45,
        ]);

        self::assertSame(6000, $entry->rate);
        self::assertSame(4500, $entry->amount);
        self::assertSame(3, $entry->currency_id);
        self::assertSame((int) $project->id, $entry->project_id);
    }

    public function test_a_later_rate_change_never_rewrites_what_was_already_logged(): void
    {
        $project = $this->makeProject(self::COMPANY, ['default_rate' => 6000]);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);
        $logged = $this->entries->create(self::COMPANY, [
            'task_id' => $task->id,
            'user_id' => self::USER,
            'duration_minutes' => 60,
        ]);

        Project::query()->whereKey($project->id)->update(['default_rate' => 9000]);

        $later = $this->entries->create(self::COMPANY, [
            'task_id' => $task->id,
            'user_id' => self::USER,
            'duration_minutes' => 60,
        ]);

        self::assertSame(6000, $logged->fresh()->rate);
        self::assertSame(6000, $logged->fresh()->amount);
        self::assertSame(9000, $later->rate);
    }

    public function test_an_invoiced_entry_keeps_the_money_that_belongs_to_its_invoice(): void
    {
        $project = $this->makeProject(self::COMPANY, ['default_rate' => 6000]);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);
        $entry = $this->makeEntry(self::COMPANY, (int) $task->id, [
            'project_id' => $project->id,
            'duration_minutes' => 60,
            'rate' => 6000,
            'amount' => 6000,
            'invoice_id' => 77,
        ]);

        Project::query()->whereKey($project->id)->update(['default_rate' => 9000]);

        $updated = $this->entries->update(self::COMPANY, (int) $entry->id, ['description' => 'Typo fix']);

        self::assertSame(6000, $updated->rate);
        self::assertSame(6000, $updated->amount);
    }

    public function test_editing_an_unbilled_entry_re_rounds_and_re_prices_it(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'rounding_minutes', 30);
        $project = $this->makeProject(self::COMPANY, ['default_rate' => 6000]);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);
        $entry = $this->entries->create(self::COMPANY, [
            'task_id' => $task->id,
            'user_id' => self::USER,
            'duration_minutes' => 30,
        ]);

        $updated = $this->entries->update(self::COMPANY, (int) $entry->id, ['duration_minutes' => 100]);

        self::assertSame(90, $updated->duration_minutes);
        self::assertSame(9000, $updated->amount);
    }

    public function test_invoiced_time_can_never_be_deleted(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $entry = $this->makeEntry(self::COMPANY, (int) $task->id, ['invoice_id' => 77]);

        $this->expectException(EntriesAlreadyInvoiced::class);

        $this->entries->delete(self::COMPANY, (int) $entry->id);
    }

    public function test_unbilled_time_is_deleted(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $entry = $this->makeEntry(self::COMPANY, (int) $task->id);

        $this->entries->delete(self::COMPANY, (int) $entry->id);

        self::assertSame(0, TimeEntry::query()->forCompany(self::COMPANY)->count());
    }

    public function test_it_never_reaches_an_entry_of_another_company(): void
    {
        $task = $this->makeTask(10);
        $foreign = $this->makeEntry(10, (int) $task->id);

        $this->expectException(ModelNotFoundException::class);

        $this->entries->findForCompany(self::COMPANY, (int) $foreign->id);
    }

    public function test_a_viewer_without_the_ability_only_ever_sees_their_own_time(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['user_id' => self::USER]);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['user_id' => 8]);

        $own = $this->entries->listFor(self::COMPANY, ['user_id' => 8], self::USER, false);
        $all = $this->entries->listFor(self::COMPANY, [], self::USER, true);

        self::assertSame([self::USER], $own->pluck('user_id')->all());
        self::assertCount(2, $all);
    }

    public function test_a_running_timer_is_not_a_timesheet_row_yet(): void
    {
        $task = $this->makeTask(self::COMPANY);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['running_user_id' => self::USER, 'ended_at' => null, 'duration_minutes' => 0]);

        self::assertCount(0, $this->entries->listFor(self::COMPANY, [], self::USER, true));
    }
}
