<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Feature;

use Illuminate\Support\Carbon;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\ProjectMember;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Tests\TestCase;

final class ProjectsApiTest extends TestCase
{
    private const COMPANY = 9;

    private const OTHER_COMPANY = 10;

    private const CUSTOMER = 42;

    public function test_it_lists_only_the_companys_projects_and_pages_them(): void
    {
        $this->makeProject(self::COMPANY, ['name' => 'Alpha']);
        $this->makeProject(self::COMPANY, ['name' => 'Beta']);
        $this->makeProject(self::OTHER_COMPANY, ['name' => 'Someone else']);

        $response = $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/projects?limit=1');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('meta.per_page', 1);
        $response->assertJsonCount(1, 'data');
        // The list opens newest first, so the second project leads the page.
        $response->assertJsonPath('data.0.name', 'Beta');
    }

    public function test_the_list_filters_by_status_customer_member_and_text(): void
    {
        $website = $this->makeProject(self::COMPANY, ['name' => 'Website', 'customer_id' => self::CUSTOMER]);
        $this->makeProject(self::COMPANY, ['name' => 'Internal tooling']);
        $archived = $this->makeProject(self::COMPANY, ['name' => 'Old site', 'status' => Project::STATUS_ARCHIVED]);
        $this->makeMember(self::COMPANY, (int) $website->id, 12);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/projects?status='.Project::STATUS_ARCHIVED)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', (int) $archived->id);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/projects?customer_id='.self::CUSTOMER)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', (int) $website->id);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/projects?member_id=12')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', (int) $website->id);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/projects?search=tooling')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Internal tooling');
    }

    public function test_the_list_opens_newest_first_and_sorts_by_every_supported_key(): void
    {
        $this->threeProjects();

        self::assertSame(['Gamma', 'Beta', 'alpha'], $this->names(''));
        self::assertSame(['alpha', 'Beta', 'Gamma'], $this->names('sort_by=created_at'));
        self::assertSame(['alpha', 'Beta', 'Gamma'], $this->names('sort_by=name'));
        self::assertSame(['Gamma', 'Beta', 'alpha'], $this->names('sort_by=name&sort_order=desc'));
        self::assertSame(['alpha', 'Gamma', 'Beta'], $this->names('sort_by=status'));
        self::assertSame(['Beta', 'alpha', 'Gamma'], $this->names('sort_by=due_date'));
        self::assertSame(['alpha', 'Beta', 'Gamma'], $this->names('sort_by=due_date&sort_order=desc'));
        self::assertSame(['alpha', 'Beta', 'Gamma'], $this->names('sort_by=default_rate'));
        self::assertSame(['Beta', 'alpha', 'Gamma'], $this->names('sort_by=default_rate&sort_order=desc'));
    }

    public function test_a_project_without_the_sorted_value_lands_last_whichever_way_the_list_runs(): void
    {
        $this->threeProjects();

        // Gamma has neither a due date nor a rate, so it never leads the page.
        self::assertSame('Gamma', $this->names('sort_by=due_date')[2]);
        self::assertSame('Gamma', $this->names('sort_by=due_date&sort_order=desc')[2]);
        self::assertSame('Gamma', $this->names('sort_by=default_rate')[2]);
        self::assertSame('Gamma', $this->names('sort_by=default_rate&sort_order=desc')[2]);
    }

    public function test_the_sort_survives_paging(): void
    {
        $this->threeProjects();

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/projects?sort_by=name&limit=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gamma');
    }

    public function test_the_list_refuses_a_sort_key_or_direction_it_does_not_know(): void
    {
        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/projects?sort_by=colour')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/projects?sort_by=name&sort_order=sideways')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort_order']);
    }

    public function test_it_creates_a_project_for_the_header_company_and_stamps_the_creator(): void
    {
        $response = $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/projects', [
            'name' => 'Website redesign',
            'customer_id' => self::CUSTOMER,
            'identifier' => 'WEB',
            'default_rate' => 12000,
            'budget_minutes' => 600,
            'due_date' => '2026-12-31',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.company_id', self::COMPANY);
        $response->assertJsonPath('data.creator_id', self::DEFAULT_USER);
        $response->assertJsonPath('data.status', Project::STATUS_ACTIVE);
        $response->assertJsonPath('data.default_rate', 12000);
        $response->assertJsonPath('data.due_date', '2026-12-31');
        $response->assertJsonPath('data.is_internal', false);

        self::assertSame(1, Project::query()->forCompany(self::COMPANY)->count());
    }

    public function test_a_project_without_a_customer_is_internal(): void
    {
        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/projects', ['name' => 'Internal tooling'])
            ->assertCreated()
            ->assertJsonPath('data.is_internal', true);
    }

    public function test_it_rejects_a_project_without_a_name(): void
    {
        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/projects', ['default_rate' => -1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'default_rate']);
    }

    public function test_the_detail_carries_the_totals_the_list_leaves_out(): void
    {
        $project = $this->makeProject(self::COMPANY, ['customer_id' => self::CUSTOMER, 'currency_id' => 3]);
        $status = $this->makeStatus(self::COMPANY, ['is_closed' => true]);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id, 'task_status_id' => $status->id, 'closed_at' => now()]);
        $this->makeEntry(self::COMPANY, (int) $task->id, ['project_id' => $project->id, 'duration_minutes' => 90, 'amount' => 15000]);

        $response = $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/projects/'.$project->id);

        $response->assertOk();
        $response->assertJsonPath('data.totals.tasks.total', 1);
        $response->assertJsonPath('data.totals.tasks.closed', 1);
        $response->assertJsonPath('data.totals.logged_minutes', 90);
        $response->assertJsonPath('data.totals.billable_amount', 15000);
        $response->assertJsonPath('data.totals.unbilled_amount', 15000);
        $response->assertJsonPath('data.totals.currency_id', 3);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/projects')
            ->assertJsonMissingPath('data.0.totals');
    }

    public function test_it_updates_a_project(): void
    {
        $project = $this->makeProject(self::COMPANY);

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/projects/'.$project->id, ['name' => 'Renamed', 'colour' => '#ff0000'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed')
            ->assertJsonPath('data.colour', '#ff0000');
    }

    public function test_archive_and_unarchive_flip_the_status(): void
    {
        $project = $this->makeProject(self::COMPANY);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/projects/'.$project->id.'/archive')
            ->assertOk()
            ->assertJsonPath('data.status', Project::STATUS_ARCHIVED);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/projects/'.$project->id.'/unarchive')
            ->assertOk()
            ->assertJsonPath('data.status', Project::STATUS_ACTIVE);
    }

    public function test_it_deletes_a_project(): void
    {
        $project = $this->makeProject(self::COMPANY);

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/projects/'.$project->id)
            ->assertOk()
            ->assertJson(['success' => true]);

        self::assertSame(0, Project::query()->forCompany(self::COMPANY)->count());
    }

    public function test_a_project_with_invoiced_time_refuses_to_be_deleted(): void
    {
        $project = $this->makeProject(self::COMPANY, ['customer_id' => self::CUSTOMER]);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);
        $this->makeEntry(self::COMPANY, (int) $task->id, [
            'project_id' => $project->id,
            'invoice_id' => 77,
            'invoice_item_id' => 88,
        ]);

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/projects/'.$project->id)
            ->assertStatus(422)
            ->assertJsonPath('error', 'project_in_use');
    }

    public function test_attaching_a_member_needs_the_user_to_be_a_company_member(): void
    {
        $project = $this->makeProject(self::COMPANY);
        $this->companyData->withMember(self::COMPANY, 12, 'Ada Lovelace');

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/projects/'.$project->id.'/members', ['user_id' => 99])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/projects/'.$project->id.'/members', ['user_id' => 12, 'rate' => 9000])
            ->assertCreated()
            ->assertJsonPath('data.user_id', 12)
            ->assertJsonPath('data.rate', 9000);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/projects/'.$project->id.'/members')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_id', 12);
    }

    public function test_detaching_a_member_leaves_their_time_alone(): void
    {
        $project = $this->makeProject(self::COMPANY);
        $this->makeMember(self::COMPANY, (int) $project->id, 12);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);
        $entry = $this->makeEntry(self::COMPANY, (int) $task->id, ['project_id' => $project->id, 'user_id' => 12]);

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/projects/'.$project->id.'/members/12')
            ->assertOk();

        self::assertSame(0, ProjectMember::query()->forCompany(self::COMPANY)->count());
        self::assertNotNull(TimeEntry::query()->find($entry->id));
    }

    public function test_a_project_of_another_company_is_not_found(): void
    {
        $project = $this->makeProject(self::OTHER_COMPANY);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/projects/'.$project->id)
            ->assertNotFound();

        $this->asCompany(self::COMPANY)
            ->putJson('/api/v1/tasks-projects/projects/'.$project->id, ['name' => 'Mine now'])
            ->assertNotFound();

        $this->asCompany(self::COMPANY)
            ->deleteJson('/api/v1/tasks-projects/projects/'.$project->id)
            ->assertNotFound();
    }

    public function test_every_action_refuses_without_its_ability(): void
    {
        $project = $this->makeProject(self::COMPANY);
        $this->companyData->withMember(self::COMPANY, 12, 'Ada Lovelace');

        $this->authorization->deny(
            Authorizes::id(Abilities::VIEW_PROJECT),
            Authorizes::id(Abilities::CREATE_PROJECT),
            Authorizes::id(Abilities::EDIT_PROJECT),
            Authorizes::id(Abilities::DELETE_PROJECT),
        );

        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/projects')->assertForbidden();
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/projects', ['name' => 'Nope'])->assertForbidden();
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/projects/'.$project->id)->assertForbidden();
        $this->asCompany(self::COMPANY)->putJson('/api/v1/tasks-projects/projects/'.$project->id, ['name' => 'Nope'])->assertForbidden();
        $this->asCompany(self::COMPANY)->deleteJson('/api/v1/tasks-projects/projects/'.$project->id)->assertForbidden();
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/projects/'.$project->id.'/archive')->assertForbidden();
        $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/projects/'.$project->id.'/members', ['user_id' => 12])->assertForbidden();
    }

    public function test_the_ability_is_checked_for_the_header_company_and_the_authenticated_user(): void
    {
        $this->asCompany(self::COMPANY)->getJson('/api/v1/tasks-projects/projects')->assertOk();

        self::assertSame([
            'user_id' => self::DEFAULT_USER,
            'company_id' => self::COMPANY,
            'ability' => 'tasks-projects:view-project',
            'resource' => null,
        ], $this->authorization->checks[0]);
    }

    /**
     * Three projects that differ in every sortable column, created a day
     * apart so `created_at` orders them without relying on the clock.
     *
     * The lowercase name is deliberate: a byte comparison would file it after
     * the capitalised ones, and a person reading the list would not.
     */
    private function threeProjects(): void
    {
        Carbon::setTestNow('2026-09-01 09:00:00');
        $this->makeProject(self::COMPANY, [
            'name' => 'alpha',
            'due_date' => '2026-12-31',
            'default_rate' => 9000,
        ]);

        Carbon::setTestNow('2026-09-02 09:00:00');
        $this->makeProject(self::COMPANY, [
            'name' => 'Beta',
            'status' => Project::STATUS_ARCHIVED,
            'due_date' => '2026-01-31',
            'default_rate' => 12000,
        ]);

        Carbon::setTestNow('2026-09-03 09:00:00');
        $this->makeProject(self::COMPANY, ['name' => 'Gamma']);

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
            ->getJson('/api/v1/tasks-projects/projects?'.$query);

        $response->assertOk();

        return array_column((array) $response->json('data'), 'name');
    }
}
