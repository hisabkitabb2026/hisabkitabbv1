<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Tests\TestCase;

final class BillingApiTest extends TestCase
{
    private const COMPANY = 9;

    private const OTHER_COMPANY = 10;

    private const CUSTOMER = 42;

    private const CURRENCY = 3;

    private const RATE = 6000;

    private Project $website;

    private Task $landing;

    private Task $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyData->withMember(self::COMPANY, self::DEFAULT_USER, 'Ada Lovelace');
        $this->website = $this->makeProject(self::COMPANY, [
            'name' => 'Website',
            'customer_id' => self::CUSTOMER,
            'currency_id' => self::CURRENCY,
        ]);
        $this->landing = $this->task('Landing page', $this->website);
        $this->pricing = $this->task('Pricing page', $this->website);
    }

    public function test_unbilled_collects_the_customers_billable_time_and_groups_it_four_ways(): void
    {
        $first = $this->entry($this->landing, 60, '2026-09-01');
        $second = $this->entry($this->pricing, 90, '2026-09-02');

        $response = $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/billing/unbilled?customer_id='.self::CUSTOMER);

        $response->assertOk();
        $response->assertJsonPath('data.customer_id', self::CUSTOMER);
        $response->assertJsonPath('data.minutes', 150);
        $response->assertJsonPath('data.entry_ids', [(int) $first->id, (int) $second->id]);
        $response->assertJsonPath('data.currencies', [
            ['currency_id' => self::CURRENCY, 'minutes' => 150, 'amount' => 15000],
        ]);
        $response->assertJsonPath('data.groups.task.0.label', 'Landing page');
        $response->assertJsonPath('data.groups.project.0.label', 'Website');
        $response->assertJsonPath('data.groups.member.0.label', 'Ada Lovelace');
        $response->assertJsonPath('data.groups.summary.0.minutes', 150);
    }

    public function test_unbilled_skips_stamped_non_billable_running_and_internal_time(): void
    {
        $open = $this->entry($this->landing, 60, '2026-09-01');
        $this->entry($this->landing, 60, '2026-09-02', ['billable' => false]);
        $this->entry($this->landing, 0, '2026-09-03', ['running_user_id' => self::DEFAULT_USER, 'ended_at' => null]);
        $this->entry($this->landing, 60, '2026-09-04', ['invoice_id' => 77, 'invoice_item_id' => 101]);
        $this->companyData->withInvoices(self::COMPANY, 77);

        $internal = $this->makeProject(self::COMPANY, ['name' => 'Internal tooling', 'customer_id' => null]);
        $stray = $this->makeTask(self::COMPANY, ['name' => 'Stray', 'project_id' => $internal->id, 'customer_id' => self::CUSTOMER]);
        $this->entry($stray, 60, '2026-09-05', ['project_id' => $internal->id]);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/billing/unbilled?customer_id='.self::CUSTOMER)
            ->assertOk()
            ->assertJsonPath('data.entry_ids', [(int) $open->id]);
    }

    public function test_time_whose_invoice_vanished_from_the_host_comes_back_to_the_unbilled_list(): void
    {
        $orphan = $this->entry($this->landing, 60, '2026-09-01', ['invoice_id' => 88, 'invoice_item_id' => 101]);
        $this->companyData->withInvoices(self::COMPANY, 77);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/billing/unbilled?customer_id='.self::CUSTOMER)
            ->assertOk()
            ->assertJsonPath('data.entry_ids', [(int) $orphan->id]);
    }

    public function test_unbilled_honours_the_date_range_and_needs_a_customer(): void
    {
        $this->entry($this->landing, 60, '2026-09-01');
        $inside = $this->entry($this->landing, 60, '2026-09-10');

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/billing/unbilled?customer_id='.self::CUSTOMER.'&from=2026-09-05&to=2026-09-15')
            ->assertOk()
            ->assertJsonPath('data.entry_ids', [(int) $inside->id]);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/billing/unbilled')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
    }

    public function test_prepare_returns_the_host_invoice_body_for_every_grouping(): void
    {
        Carbon::setTestNow('2026-09-15 08:00:00');
        $first = $this->entry($this->landing, 60, '2026-09-01');
        $second = $this->entry($this->pricing, 90, '2026-09-02');
        $ids = [(int) $first->id, (int) $second->id];

        $byTask = $this->prepare($ids, 'task');

        $byTask->assertOk();
        $byTask->assertJsonPath('data.invoice_date', '2026-09-15');
        $byTask->assertJsonPath('data.customer_id', self::CUSTOMER);
        $byTask->assertJsonPath('data.currency_id', self::CURRENCY);
        $byTask->assertJsonPath('data.sub_total', 15000);
        $byTask->assertJsonPath('data.total', 15000);
        $byTask->assertJsonPath('data.notes', null);
        $byTask->assertJsonPath('data.template_name', null);
        $byTask->assertJsonPath('data.taxes', []);
        $byTask->assertJsonPath('data.items', [
            $this->line('#1 Landing page', 1.0, 6000, '2026-09-01  1.00 h'),
            $this->line('#2 Pricing page', 1.5, 9000, '2026-09-02  1.50 h'),
        ]);
        $byTask->assertJsonPath('data.groups', [
            ['entry_ids' => [(int) $first->id]],
            ['entry_ids' => [(int) $second->id]],
        ]);

        $this->prepare($ids, 'project')->assertJsonPath('data.items.0.name', 'Website')->assertJsonPath('data.items.0.quantity', 2.5);
        $this->prepare($ids, 'member')->assertJsonPath('data.items.0.name', 'Ada Lovelace');
        $this->prepare($ids, 'summary')
            ->assertJsonPath('data.items.0.name', 'Time')
            ->assertJsonPath('data.items.0.quantity', 2.5)
            ->assertJsonPath('data.groups', [['entry_ids' => $ids]]);
    }

    public function test_prepare_refuses_a_grouping_it_does_not_know_and_an_empty_selection(): void
    {
        $entry = $this->entry($this->landing, 60, '2026-09-01');

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/billing/prepare', ['entry_ids' => [(int) $entry->id], 'grouping' => 'weekday'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['grouping']);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/billing/prepare', ['entry_ids' => [], 'grouping' => 'task'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['entry_ids']);
    }

    public function test_prepare_refuses_a_selection_spanning_two_customers(): void
    {
        $ours = $this->entry($this->landing, 60, '2026-09-01');
        $theirs = $this->entry($this->task('Theirs', null, 43), 60, '2026-09-02');

        $this->prepare([(int) $ours->id, (int) $theirs->id], 'task')
            ->assertStatus(422)
            ->assertJsonPath('error', 'mixed_billing_selection');
    }

    public function test_prepare_refuses_an_entry_of_another_company(): void
    {
        $foreignTask = $this->makeTask(self::OTHER_COMPANY, ['customer_id' => self::CUSTOMER]);
        $foreign = $this->makeEntry(self::OTHER_COMPANY, (int) $foreignTask->id);

        $this->prepare([(int) $foreign->id], 'task')
            ->assertStatus(422)
            ->assertJsonPath('error', 'unknown_time_entries');
    }

    public function test_prepare_refuses_non_billable_time_and_time_with_no_customer(): void
    {
        $free = $this->entry($this->landing, 60, '2026-09-01', ['billable' => false]);

        $this->prepare([(int) $free->id], 'task')
            ->assertStatus(422)
            ->assertJsonPath('error', 'not_billable');

        $internal = $this->makeProject(self::COMPANY, ['name' => 'Internal tooling', 'customer_id' => null]);
        $task = $this->makeTask(self::COMPANY, ['name' => 'Internal work', 'project_id' => $internal->id]);
        $entry = $this->entry($task, 60, '2026-09-02', ['project_id' => $internal->id]);

        $this->prepare([(int) $entry->id], 'task')
            ->assertStatus(422)
            ->assertJsonPath('error', 'not_billable');
    }

    public function test_prepare_refuses_time_that_is_already_on_a_live_invoice(): void
    {
        $entry = $this->entry($this->landing, 60, '2026-09-01', ['invoice_id' => 77, 'invoice_item_id' => 101]);
        $this->companyData->withInvoices(self::COMPANY, 77);

        $this->prepare([(int) $entry->id], 'task')
            ->assertStatus(422)
            ->assertJsonPath('error', 'entries_already_invoiced');
    }

    public function test_confirm_stamps_the_entries_and_is_safe_to_repeat(): void
    {
        $first = $this->entry($this->landing, 60, '2026-09-01');
        $second = $this->entry($this->pricing, 60, '2026-09-02');
        $payload = [
            'invoice_id' => 77,
            'items' => [
                ['invoice_item_id' => 101, 'entry_ids' => [(int) $first->id]],
                ['invoice_item_id' => 102, 'entry_ids' => [(int) $second->id]],
            ],
        ];

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/billing/confirm', $payload)
            ->assertOk()
            ->assertExactJson(['stamped' => 2]);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/billing/confirm', $payload)
            ->assertOk()
            ->assertExactJson(['stamped' => 0]);

        self::assertSame(77, $first->fresh()?->invoice_id);
        self::assertSame(101, $first->fresh()?->invoice_item_id);

        $this->companyData->withInvoices(self::COMPANY, 77);
        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/billing/unbilled?customer_id='.self::CUSTOMER)
            ->assertJsonPath('data.entry_ids', []);
    }

    public function test_confirm_refuses_an_entry_that_belongs_to_another_invoice(): void
    {
        $entry = $this->entry($this->landing, 60, '2026-09-01', ['invoice_id' => 77, 'invoice_item_id' => 101]);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/billing/confirm', [
                'invoice_id' => 78,
                'items' => [['invoice_item_id' => 201, 'entry_ids' => [(int) $entry->id]]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'entries_already_invoiced');
    }

    public function test_confirm_refuses_an_entry_of_another_company_and_stamps_nothing(): void
    {
        $ours = $this->entry($this->landing, 60, '2026-09-01');
        $foreignTask = $this->makeTask(self::OTHER_COMPANY);
        $foreign = $this->makeEntry(self::OTHER_COMPANY, (int) $foreignTask->id);

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/billing/confirm', [
                'invoice_id' => 77,
                'items' => [['invoice_item_id' => 101, 'entry_ids' => [(int) $ours->id, (int) $foreign->id]]],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'unknown_time_entries');

        self::assertNull($ours->fresh()?->invoice_id);
    }

    public function test_billing_needs_the_invoice_tasks_ability(): void
    {
        $entry = $this->entry($this->landing, 60, '2026-09-01');
        $this->authorization->deny(Authorizes::id(Abilities::INVOICE_TASKS));

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/billing/customers')
            ->assertForbidden();

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/billing/unbilled?customer_id='.self::CUSTOMER)
            ->assertForbidden();

        $this->prepare([(int) $entry->id], 'task')->assertForbidden();

        $this->asCompany(self::COMPANY)
            ->postJson('/api/v1/tasks-projects/billing/confirm', [
                'invoice_id' => 77,
                'items' => [['invoice_item_id' => 101, 'entry_ids' => [(int) $entry->id]]],
            ])
            ->assertForbidden();
    }

    public function test_unbilled_lists_the_entries_behind_the_totals(): void
    {
        $entry = $this->entry($this->landing, 90, '2026-09-01', ['description' => 'Hero section']);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/billing/unbilled?customer_id='.self::CUSTOMER)
            ->assertOk()
            ->assertJsonPath('data.entries', [[
                'id' => (int) $entry->id,
                'task_id' => (int) $this->landing->id,
                'task_name' => 'Landing page',
                'project_id' => (int) $this->website->id,
                'project_name' => 'Website',
                'user_id' => self::DEFAULT_USER,
                'user_name' => 'Ada Lovelace',
                'date' => '2026-09-01',
                'minutes' => 90,
                'amount' => 9000,
                'rate' => self::RATE,
                'currency_id' => self::CURRENCY,
                'description' => 'Hero section',
            ]]);
    }

    public function test_customers_lists_who_has_time_waiting_to_be_invoiced(): void
    {
        $this->entry($this->landing, 60, '2026-09-01');
        $this->entry($this->pricing, 90, '2026-09-02');
        $this->entry($this->task('Their logo', null, 43), 30, '2026-09-03');
        $this->entry($this->landing, 60, '2026-09-04', ['billable' => false]);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/billing/customers')
            ->assertOk()
            ->assertJsonPath('data', [
                ['customer_id' => self::CUSTOMER, 'entries' => 2, 'minutes' => 150, 'amount' => 15000, 'currency_id' => self::CURRENCY],
                ['customer_id' => 43, 'entries' => 1, 'minutes' => 30, 'amount' => 3000, 'currency_id' => self::CURRENCY],
            ]);
    }

    public function test_customers_honours_the_date_range(): void
    {
        $this->entry($this->landing, 60, '2026-09-01');
        $this->entry($this->landing, 90, '2026-09-10');

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/billing/customers?from=2026-09-05&to=2026-09-15')
            ->assertOk()
            ->assertJsonPath('data', [
                ['customer_id' => self::CUSTOMER, 'entries' => 1, 'minutes' => 90, 'amount' => 9000, 'currency_id' => self::CURRENCY],
            ]);
    }

    public function test_prepare_invoices_a_selection_of_tasks(): void
    {
        $first = $this->entry($this->landing, 60, '2026-09-01');
        $second = $this->entry($this->pricing, 90, '2026-09-02');
        $this->entry($this->task('Theirs', null, 43), 60, '2026-09-03');

        $this->prepareBody(['task_ids' => [(int) $this->landing->id, (int) $this->pricing->id]])
            ->assertOk()
            ->assertJsonPath('data.customer_id', self::CUSTOMER)
            ->assertJsonPath('data.total', 15000)
            ->assertJsonPath('data.items', [
                $this->line('#1 Landing page', 1.0, 6000, '2026-09-01  1.00 h'),
                $this->line('#2 Pricing page', 1.5, 9000, '2026-09-02  1.50 h'),
            ])
            ->assertJsonPath('data.groups', [
                ['entry_ids' => [(int) $first->id]],
                ['entry_ids' => [(int) $second->id]],
            ]);
    }

    public function test_prepare_invoices_a_whole_project(): void
    {
        $this->entry($this->landing, 60, '2026-09-01');
        $this->entry($this->pricing, 90, '2026-09-02');
        $this->entry($this->task('Ad hoc call', null), 45, '2026-09-03');

        $this->prepareBody(['project_id' => (int) $this->website->id])
            ->assertOk()
            ->assertJsonPath('data.total', 15000)
            ->assertJsonPath('data.items.0.name', '#1 Landing page')
            ->assertJsonPath('data.items.1.name', '#2 Pricing page')
            ->assertJsonCount(2, 'data.items');
    }

    public function test_prepare_takes_exactly_one_kind_of_selection(): void
    {
        $entry = $this->entry($this->landing, 60, '2026-09-01');

        $this->prepareBody([])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['entry_ids', 'task_ids', 'project_id']);

        $this->prepareBody(['entry_ids' => [(int) $entry->id], 'task_ids' => [(int) $this->landing->id]])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['entry_ids', 'task_ids']);

        $this->prepareBody(['task_ids' => [(int) $this->landing->id], 'project_id' => (int) $this->website->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['task_ids', 'project_id']);
    }

    public function test_prepare_says_when_a_selection_has_nothing_left_to_bill(): void
    {
        $this->entry($this->landing, 60, '2026-09-01', ['billable' => false]);

        $this->prepareBody(['task_ids' => [(int) $this->landing->id]])
            ->assertStatus(422)
            ->assertJsonPath('error', 'nothing_to_invoice')
            ->assertJsonPath('message', 'No unbilled billable time on the selected tasks.');

        $this->prepareBody(['project_id' => (int) $this->website->id])
            ->assertStatus(422)
            ->assertJsonPath('error', 'nothing_to_invoice');
    }

    public function test_prepare_names_the_customers_a_mixed_task_selection_spans(): void
    {
        $theirs = $this->task('Theirs', null, 43);
        $this->entry($this->landing, 60, '2026-09-01');
        $this->entry($theirs, 60, '2026-09-02');

        $this->prepareBody(['task_ids' => [(int) $this->landing->id, (int) $theirs->id]])
            ->assertStatus(422)
            ->assertJsonPath('error', 'mixed_billing_selection')
            ->assertJsonPath('customer_ids', [self::CUSTOMER, 43]);
    }

    public function test_prepare_refuses_a_task_or_a_project_of_another_company(): void
    {
        $foreignTask = $this->makeTask(self::OTHER_COMPANY, ['customer_id' => self::CUSTOMER]);
        $foreignProject = $this->makeProject(self::OTHER_COMPANY, ['name' => 'Theirs', 'customer_id' => self::CUSTOMER]);

        $this->prepareBody(['task_ids' => [(int) $foreignTask->id]])->assertNotFound();
        $this->prepareBody(['project_id' => (int) $foreignProject->id])->assertNotFound();
    }

    /** One prepared invoice line, with the zeroed keys the host writer reads. */
    private function line(string $name, float $quantity, int $total, ?string $description = null): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'quantity' => $quantity,
            'price' => self::RATE,
            'discount_type' => 'fixed',
            'discount' => 0,
            'discount_val' => 0,
            'tax' => 0,
            'taxes' => [],
            'total' => $total,
        ];
    }

    /** @param list<int> $entryIds */
    private function prepare(array $entryIds, string $grouping): TestResponse
    {
        return $this->prepareBody(['entry_ids' => $entryIds, 'grouping' => $grouping]);
    }

    /** @param array<string, mixed> $body */
    private function prepareBody(array $body): TestResponse
    {
        return $this->asCompany(self::COMPANY)->postJson('/api/v1/tasks-projects/billing/prepare', $body);
    }

    private function task(string $name, ?Project $project, int $customerId = self::CUSTOMER): Task
    {
        return $this->makeTask(self::COMPANY, [
            'name' => $name,
            'project_id' => $project?->id,
            'customer_id' => $project === null ? $customerId : $project->customer_id,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function entry(Task $task, int $minutes, string $day, array $attributes = []): TimeEntry
    {
        return $this->makeEntry(self::COMPANY, (int) $task->id, $attributes + [
            'project_id' => $task->project_id,
            'user_id' => self::DEFAULT_USER,
            'started_at' => Carbon::parse($day.' 09:00:00'),
            'ended_at' => Carbon::parse($day.' 09:00:00')->addMinutes($minutes),
            'duration_minutes' => $minutes,
            'rate' => self::RATE,
            'amount' => (int) round($minutes / 60 * self::RATE),
            'currency_id' => self::CURRENCY,
        ]);
    }
}
