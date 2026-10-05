<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\TasksProjects\Application\Exceptions\ProjectInUse;
use Modules\TasksProjects\Application\ProjectMemberService;
use Modules\TasksProjects\Application\ProjectService;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\ProjectMember;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Tests\TestCase;

final class ProjectServiceTest extends TestCase
{
    private const COMPANY = 9;

    private ProjectService $projects;

    private ProjectMemberService $members;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projects = new ProjectService($this->companyData);
        $this->members = new ProjectMemberService($this->projects);
    }

    public function test_a_new_project_starts_active(): void
    {
        $project = $this->projects->create(self::COMPANY, ['name' => 'Website', 'customer_id' => 42]);

        self::assertSame(Project::STATUS_ACTIVE, $project->status);
        self::assertSame(self::COMPANY, $project->company_id);
        self::assertFalse($project->isInternal());
    }

    public function test_a_project_without_a_customer_is_internal(): void
    {
        $project = $this->projects->create(self::COMPANY, ['name' => 'Internal tooling']);

        self::assertTrue($project->isInternal());
    }

    public function test_it_never_reaches_a_project_of_another_company(): void
    {
        $foreign = $this->makeProject(10);

        $this->expectException(ModelNotFoundException::class);

        $this->projects->findForCompany(self::COMPANY, (int) $foreign->id);
    }

    public function test_listing_is_scoped_to_the_company_and_filtered(): void
    {
        $this->projects->create(self::COMPANY, ['name' => 'Alpha', 'customer_id' => 42]);
        $archived = $this->projects->create(self::COMPANY, ['name' => 'Beta', 'customer_id' => 43]);
        $this->projects->archive(self::COMPANY, (int) $archived->id);
        $this->projects->create(10, ['name' => 'Elsewhere']);

        // Newest first by default, and two rows of the same second break on the id.
        self::assertSame(['Beta', 'Alpha'], $this->projects->listFor(self::COMPANY)->pluck('name')->all());
        self::assertSame(['Alpha'], $this->projects->listFor(self::COMPANY, ['status' => Project::STATUS_ACTIVE])->pluck('name')->all());
        self::assertSame(['Beta'], $this->projects->listFor(self::COMPANY, ['customer_id' => 43])->pluck('name')->all());
    }

    public function test_archiving_and_unarchiving_flips_the_status(): void
    {
        $project = $this->projects->create(self::COMPANY, ['name' => 'Website']);

        self::assertSame(Project::STATUS_ARCHIVED, $this->projects->archive(self::COMPANY, (int) $project->id)->status);
        self::assertSame(Project::STATUS_ACTIVE, $this->projects->unarchive(self::COMPANY, (int) $project->id)->status);
    }

    public function test_changing_the_customer_rewrites_the_tasks_that_follow_the_project(): void
    {
        $project = $this->projects->create(self::COMPANY, ['name' => 'Website', 'customer_id' => 42]);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id, 'customer_id' => 42]);

        $this->projects->update(self::COMPANY, (int) $project->id, ['customer_id' => 43]);

        self::assertSame(43, $task->fresh()->customer_id);
    }

    public function test_a_new_project_inherits_the_currency_of_its_customer(): void
    {
        $this->companyData->withCustomer(self::COMPANY, 42, 3);

        $project = $this->projects->create(self::COMPANY, ['name' => 'Website', 'customer_id' => 42]);

        self::assertSame(3, $project->currency_id);
    }

    public function test_a_named_currency_survives_the_customer_it_was_filed_under(): void
    {
        $this->companyData->withCustomer(self::COMPANY, 42, 3);

        $project = $this->projects->create(self::COMPANY, [
            'name' => 'Website',
            'customer_id' => 42,
            'currency_id' => 4,
        ]);

        self::assertSame(4, $project->currency_id);
    }

    public function test_an_internal_project_and_a_customer_without_a_currency_stay_currencyless(): void
    {
        $this->companyData->withCustomer(self::COMPANY, 43, null);

        $internal = $this->projects->create(self::COMPANY, ['name' => 'Internal tooling']);
        $unpriced = $this->projects->create(self::COMPANY, ['name' => 'Favour', 'customer_id' => 43]);

        self::assertNull($internal->currency_id);
        self::assertNull($unpriced->currency_id);
    }

    public function test_changing_the_customer_moves_the_project_to_that_customers_currency(): void
    {
        $this->companyData->withCustomer(self::COMPANY, 42, 3)->withCustomer(self::COMPANY, 43, 4);

        $project = $this->projects->create(self::COMPANY, ['name' => 'Website', 'customer_id' => 42]);
        $moved = $this->projects->update(self::COMPANY, (int) $project->id, ['customer_id' => 43]);

        self::assertSame(4, $moved->currency_id);
    }

    public function test_an_update_that_leaves_the_customer_alone_leaves_the_currency_alone(): void
    {
        $this->companyData->withCustomer(self::COMPANY, 42, 3);

        $project = $this->projects->create(self::COMPANY, [
            'name' => 'Website',
            'customer_id' => 42,
            'currency_id' => 4,
        ]);
        $renamed = $this->projects->update(self::COMPANY, (int) $project->id, ['name' => 'Website 2']);

        self::assertSame(4, $renamed->currency_id);
    }

    public function test_an_explicit_null_currency_clears_it(): void
    {
        $this->companyData->withCustomer(self::COMPANY, 42, 3);

        $project = $this->projects->create(self::COMPANY, ['name' => 'Website', 'customer_id' => 42]);
        $cleared = $this->projects->update(self::COMPANY, (int) $project->id, [
            'customer_id' => 42,
            'currency_id' => null,
        ]);

        self::assertNull($cleared->currency_id);
    }

    public function test_a_member_is_attached_with_a_rate_and_reattaching_updates_it(): void
    {
        $project = $this->projects->create(self::COMPANY, ['name' => 'Website']);

        $this->members->attach(self::COMPANY, (int) $project->id, 7, 5000);
        $this->members->attach(self::COMPANY, (int) $project->id, 7, 6000);

        $members = $this->members->listFor(self::COMPANY, (int) $project->id);

        self::assertCount(1, $members);
        self::assertSame(6000, $members->first()->rate);
    }

    public function test_detaching_a_member_leaves_their_time_entries_alone(): void
    {
        $project = $this->projects->create(self::COMPANY, ['name' => 'Website']);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);
        $this->members->attach(self::COMPANY, (int) $project->id, 7, 5000);
        $entry = $this->makeEntry(self::COMPANY, (int) $task->id, ['project_id' => $project->id, 'user_id' => 7]);

        $this->members->detach(self::COMPANY, (int) $project->id, 7);

        self::assertSame(0, ProjectMember::query()->forCompany(self::COMPANY)->count());
        self::assertSame(7, $entry->fresh()->user_id);
    }

    public function test_detaching_someone_who_is_not_a_member_is_refused(): void
    {
        $project = $this->projects->create(self::COMPANY, ['name' => 'Website']);

        $this->expectException(ModelNotFoundException::class);

        $this->members->detach(self::COMPANY, (int) $project->id, 7);
    }

    public function test_members_of_another_companys_project_are_out_of_reach(): void
    {
        $foreign = $this->makeProject(10);

        $this->expectException(ModelNotFoundException::class);

        $this->members->listFor(self::COMPANY, (int) $foreign->id);
    }

    public function test_deleting_a_project_removes_its_members_tasks_and_time(): void
    {
        $project = $this->projects->create(self::COMPANY, ['name' => 'Website']);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);
        $this->members->attach(self::COMPANY, (int) $project->id, 7);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['project_id' => $project->id]);

        $this->projects->delete(self::COMPANY, (int) $project->id);

        self::assertSame(0, Project::query()->forCompany(self::COMPANY)->count());
        self::assertSame(0, Task::query()->forCompany(self::COMPANY)->count());
        self::assertSame(0, TimeEntry::query()->forCompany(self::COMPANY)->count());
        self::assertSame(0, ProjectMember::query()->forCompany(self::COMPANY)->count());
    }

    public function test_a_project_with_invoiced_time_is_archived_not_deleted(): void
    {
        $project = $this->projects->create(self::COMPANY, ['name' => 'Website']);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['project_id' => $project->id, 'invoice_id' => 77]);

        $this->expectException(ProjectInUse::class);
        $this->expectExceptionMessage("Project {$project->id} has invoiced time entries and cannot be deleted.");

        $this->projects->delete(self::COMPANY, (int) $project->id);
    }

    public function test_totals_count_tasks_and_split_billable_from_unbilled_money(): void
    {
        $project = $this->projects->create(self::COMPANY, ['name' => 'Website', 'customer_id' => 42, 'currency_id' => 3]);
        $open = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);
        $this->makeTask(self::COMPANY, [
            'project_id' => $project->id,
            'task_status_id' => $open->task_status_id,
            'closed_at' => '2026-09-10 10:00:00',
        ]);

        $this->makeEntry(self::COMPANY, (int) $open->id, ['project_id' => $project->id, 'duration_minutes' => 60, 'rate' => 10000, 'amount' => 10000]);
        $this->makeEntry(self::COMPANY, (int) $open->id, ['project_id' => $project->id, 'duration_minutes' => 30, 'rate' => 10000, 'amount' => 5000, 'invoice_id' => 77]);
        $this->makeEntry(self::COMPANY, (int) $open->id, ['project_id' => $project->id, 'duration_minutes' => 45, 'rate' => 10000, 'amount' => 7500, 'billable' => false]);

        $totals = $this->projects->totals($project->fresh());

        self::assertSame(['total' => 2, 'open' => 1, 'closed' => 1], $totals['tasks']);
        self::assertSame(135, $totals['logged_minutes']);
        self::assertSame(90, $totals['billable_minutes']);
        self::assertSame(15000, $totals['billable_amount']);
        self::assertSame(10000, $totals['unbilled_amount']);
        self::assertSame(3, $totals['currency_id']);
    }
}
