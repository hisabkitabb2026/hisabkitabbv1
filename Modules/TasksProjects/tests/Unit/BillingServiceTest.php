<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Modules\TasksProjects\Application\BillingSelection;
use Modules\TasksProjects\Application\BillingService;
use Modules\TasksProjects\Application\Exceptions\EntriesAlreadyInvoiced;
use Modules\TasksProjects\Application\Exceptions\MixedBillingSelection;
use Modules\TasksProjects\Application\Exceptions\NotBillable;
use Modules\TasksProjects\Application\Exceptions\NothingToInvoice;
use Modules\TasksProjects\Application\Exceptions\UnknownTimeEntries;
use Modules\TasksProjects\Application\InvoiceLineComposer;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\ModuleSettings;
use Modules\TasksProjects\Tests\TestCase;

final class BillingServiceTest extends TestCase
{
    private const COMPANY = 9;

    private const CUSTOMER = 42;

    private const CURRENCY = 3;

    private const RATE = 6000;

    private BillingService $billing;

    private Project $website;

    private Project $mobile;

    private Task $landing;

    private Task $pricing;

    private Task $onboarding;

    private Task $adHoc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->billing = new BillingService($this->companyData, $this->moduleSettings(), new InvoiceLineComposer);
        $this->companyData->withMember(self::COMPANY, 7, 'Ada Lovelace')->withMember(self::COMPANY, 8, 'Grace Hopper');

        $this->website = $this->makeProject(self::COMPANY, ['name' => 'Website', 'customer_id' => self::CUSTOMER, 'currency_id' => self::CURRENCY]);
        $this->mobile = $this->makeProject(self::COMPANY, ['name' => 'Mobile app', 'customer_id' => self::CUSTOMER, 'currency_id' => self::CURRENCY]);

        $this->landing = $this->task('Landing page', $this->website);
        $this->pricing = $this->task('Pricing page', $this->website);
        $this->onboarding = $this->task('Onboarding flow', $this->mobile);
        $this->adHoc = $this->task('Ad hoc call', null);
    }

    public function test_unbilled_collects_the_customers_billable_time_per_currency(): void
    {
        $this->entries();

        $unbilled = $this->billing->unbilled(self::COMPANY, self::CUSTOMER);

        self::assertSame(345, $unbilled['minutes']);
        self::assertSame([['currency_id' => self::CURRENCY, 'minutes' => 345, 'amount' => 34500]], $unbilled['currencies']);
        self::assertSame(['Landing page', 'Pricing page', 'Onboarding flow', 'Ad hoc call'], array_column($unbilled['groups']['task'], 'label'));
        self::assertSame(['Website', 'Mobile app', 'No project'], array_column($unbilled['groups']['project'], 'label'));
        self::assertSame(['Ada Lovelace', 'Grace Hopper'], array_column($unbilled['groups']['member'], 'label'));
        self::assertSame([345], array_column($unbilled['groups']['summary'], 'minutes'));
    }

    public function test_unbilled_never_shows_non_billable_time_a_running_timer_or_another_customer(): void
    {
        $billable = $this->entry($this->landing, 7, 60, '2026-09-01');
        $this->entry($this->landing, 7, 60, '2026-09-02', ['billable' => false]);
        $this->entry($this->landing, 7, 0, '2026-09-03', ['running_user_id' => 7, 'ended_at' => null]);
        $otherCustomer = $this->task('Other customer', null, 43);
        $this->entry($otherCustomer, 7, 60, '2026-09-04');

        self::assertSame([(int) $billable->id], $this->billing->unbilled(self::COMPANY, self::CUSTOMER)['entry_ids']);
    }

    public function test_unbilled_never_shows_time_on_an_internal_project(): void
    {
        $internal = $this->makeProject(self::COMPANY, ['name' => 'Internal tooling', 'customer_id' => null]);
        $strayTask = $this->task('Stray', $internal);
        Task::query()->whereKey($strayTask->id)->update(['customer_id' => self::CUSTOMER]);
        $this->entry($strayTask, 7, 60, '2026-09-01', ['project_id' => $internal->id]);

        self::assertSame([], $this->billing->unbilled(self::COMPANY, self::CUSTOMER)['entry_ids']);
    }

    public function test_unbilled_drops_an_entry_whose_invoice_still_exists(): void
    {
        $open = $this->entry($this->landing, 7, 60, '2026-09-01');
        $this->entry($this->landing, 7, 60, '2026-09-02', ['invoice_id' => 77, 'invoice_item_id' => 5]);
        $this->companyData->withInvoices(self::COMPANY, 77);

        $unbilled = $this->billing->unbilled(self::COMPANY, self::CUSTOMER);

        self::assertSame([(int) $open->id], $unbilled['entry_ids']);
        self::assertSame([['company_id' => self::COMPANY, 'invoice_ids' => [77]]], $this->companyData->invoiceLookups);
    }

    public function test_an_entry_whose_invoice_vanished_from_the_host_becomes_unbilled_again(): void
    {
        $open = $this->entry($this->landing, 7, 60, '2026-09-01');
        $orphan = $this->entry($this->landing, 7, 60, '2026-09-02', ['invoice_id' => 88, 'invoice_item_id' => 5]);
        $this->companyData->withInvoices(self::COMPANY, 77);

        self::assertSame(
            [(int) $open->id, (int) $orphan->id],
            $this->billing->unbilled(self::COMPANY, self::CUSTOMER)['entry_ids'],
        );
    }

    public function test_unbilled_honours_the_date_range(): void
    {
        $this->entry($this->landing, 7, 60, '2026-09-01');
        $inside = $this->entry($this->landing, 7, 60, '2026-09-10');
        $this->entry($this->landing, 7, 60, '2026-09-20');

        self::assertSame(
            [(int) $inside->id],
            $this->billing->unbilled(self::COMPANY, self::CUSTOMER, '2026-09-05', '2026-09-15')['entry_ids'],
        );
    }

    public function test_prepare_builds_one_line_per_task(): void
    {
        Carbon::setTestNow('2026-09-15 08:00:00');
        $entries = $this->entries();

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds($this->ids($entries)), 'task');

        self::assertSame('2026-09-15', $payload['invoice_date']);
        self::assertSame(self::CUSTOMER, $payload['customer_id']);
        self::assertSame(self::CURRENCY, $payload['currency_id']);
        self::assertSame(0, $payload['discount']);
        self::assertSame('fixed', $payload['discount_type']);
        self::assertSame(0, $payload['discount_val']);
        self::assertSame(0, $payload['tax']);
        self::assertSame(34500, $payload['sub_total']);
        self::assertSame(34500, $payload['total']);

        self::assertSame([
            ['name' => '#1 Landing page', 'description' => "2026-09-01  1.00 h\n2026-09-02  0.50 h", 'quantity' => 1.5, 'price' => 6000, 'discount_type' => 'fixed', 'discount' => 0, 'discount_val' => 0, 'tax' => 0, 'taxes' => [], 'total' => 9000],
            ['name' => '#2 Pricing page', 'description' => '2026-09-03  1.50 h', 'quantity' => 1.5, 'price' => 6000, 'discount_type' => 'fixed', 'discount' => 0, 'discount_val' => 0, 'tax' => 0, 'taxes' => [], 'total' => 9000],
            ['name' => '#3 Onboarding flow', 'description' => '2026-09-04  2.00 h', 'quantity' => 2.0, 'price' => 6000, 'discount_type' => 'fixed', 'discount' => 0, 'discount_val' => 0, 'tax' => 0, 'taxes' => [], 'total' => 12000],
            ['name' => '#4 Ad hoc call', 'description' => '2026-09-05  0.75 h', 'quantity' => 0.75, 'price' => 6000, 'discount_type' => 'fixed', 'discount' => 0, 'discount_val' => 0, 'tax' => 0, 'taxes' => [], 'total' => 4500],
        ], $payload['items']);

        self::assertSame([
            ['entry_ids' => [(int) $entries[0]->id, (int) $entries[1]->id]],
            ['entry_ids' => [(int) $entries[2]->id]],
            ['entry_ids' => [(int) $entries[3]->id]],
            ['entry_ids' => [(int) $entries[4]->id]],
        ], $payload['groups']);
        self::assertCount(count($payload['items']), $payload['groups']);
    }

    public function test_prepare_carries_every_key_the_host_invoice_writer_reads(): void
    {
        $entries = $this->entries();

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds($this->ids($entries)), 'summary');

        self::assertSame([
            'invoice_date',
            'customer_id',
            'currency_id',
            'discount',
            'discount_type',
            'discount_val',
            'tax',
            'sub_total',
            'total',
            'notes',
            'template_name',
            'taxes',
            'items',
            'groups',
        ], array_keys($payload));

        self::assertNull($payload['notes']);
        self::assertNull($payload['template_name']);
        self::assertSame([], $payload['taxes']);

        self::assertSame([
            'name',
            'description',
            'quantity',
            'price',
            'discount_type',
            'discount',
            'discount_val',
            'tax',
            'taxes',
            'total',
        ], array_keys($payload['items'][0]));
    }

    public function test_unbilled_lists_every_entry_with_the_names_the_review_step_shows(): void
    {
        $first = $this->entry($this->landing, 7, 60, '2026-09-01', ['description' => 'Hero section']);
        $adHoc = $this->entry($this->adHoc, 8, 45, '2026-09-05');

        $unbilled = $this->billing->unbilled(self::COMPANY, self::CUSTOMER);

        self::assertSame([
            [
                'id' => (int) $first->id,
                'task_id' => (int) $this->landing->id,
                'task_name' => 'Landing page',
                'project_id' => (int) $this->website->id,
                'project_name' => 'Website',
                'user_id' => 7,
                'user_name' => 'Ada Lovelace',
                'date' => '2026-09-01',
                'minutes' => 60,
                'amount' => 6000,
                'rate' => self::RATE,
                'currency_id' => self::CURRENCY,
                'description' => 'Hero section',
            ],
            [
                'id' => (int) $adHoc->id,
                'task_id' => (int) $this->adHoc->id,
                'task_name' => 'Ad hoc call',
                'project_id' => null,
                'project_name' => null,
                'user_id' => 8,
                'user_name' => 'Grace Hopper',
                'date' => '2026-09-05',
                'minutes' => 45,
                'amount' => 4500,
                'rate' => self::RATE,
                'currency_id' => self::CURRENCY,
                'description' => null,
            ],
        ], $unbilled['entries']);
    }

    public function test_unbilled_names_an_entry_logged_by_someone_who_has_left(): void
    {
        $this->entry($this->landing, 99, 60, '2026-09-01');

        self::assertSame(
            ['Removed member'],
            array_column($this->billing->unbilled(self::COMPANY, self::CUSTOMER)['entries'], 'user_name'),
        );
    }

    public function test_customers_rolls_up_the_unbilled_time_of_every_customer(): void
    {
        $this->entries();
        $this->entry($this->task('Their logo', null, 43), 7, 30, '2026-09-06');

        self::assertSame([
            ['customer_id' => self::CUSTOMER, 'entries' => 5, 'minutes' => 345, 'amount' => 34500, 'currency_id' => self::CURRENCY],
            ['customer_id' => 43, 'entries' => 1, 'minutes' => 30, 'amount' => 3000, 'currency_id' => self::CURRENCY],
        ], $this->billing->customers(self::COMPANY));
    }

    public function test_customers_gives_a_customer_billed_in_two_currencies_a_row_each(): void
    {
        $this->entry($this->landing, 7, 60, '2026-09-01');
        $this->entry($this->landing, 7, 60, '2026-09-02', ['currency_id' => 4]);

        self::assertSame([
            ['customer_id' => self::CUSTOMER, 'entries' => 1, 'minutes' => 60, 'amount' => 6000, 'currency_id' => self::CURRENCY],
            ['customer_id' => self::CUSTOMER, 'entries' => 1, 'minutes' => 60, 'amount' => 6000, 'currency_id' => 4],
        ], $this->billing->customers(self::COMPANY));
    }

    public function test_customers_applies_the_same_rule_the_unbilled_list_does(): void
    {
        $this->entry($this->landing, 7, 60, '2026-09-01');
        $this->entry($this->landing, 7, 60, '2026-09-02', ['billable' => false]);
        $this->entry($this->landing, 7, 0, '2026-09-03', ['running_user_id' => 7, 'ended_at' => null]);
        $this->entry($this->landing, 7, 60, '2026-09-04', ['invoice_id' => 77, 'invoice_item_id' => 5]);
        $this->companyData->withInvoices(self::COMPANY, 77);

        $internal = $this->makeProject(self::COMPANY, ['name' => 'Internal tooling', 'customer_id' => null]);
        $stray = $this->task('Stray', $internal);
        Task::query()->whereKey($stray->id)->update(['customer_id' => self::CUSTOMER]);
        $this->entry($stray, 7, 60, '2026-09-05', ['project_id' => $internal->id]);

        self::assertSame([
            ['customer_id' => self::CUSTOMER, 'entries' => 1, 'minutes' => 60, 'amount' => 6000, 'currency_id' => self::CURRENCY],
        ], $this->billing->customers(self::COMPANY));
    }

    public function test_customers_honours_the_date_range_and_ignores_another_company(): void
    {
        $this->entry($this->landing, 7, 60, '2026-09-01');
        $this->entry($this->landing, 7, 90, '2026-09-10');
        $foreignTask = $this->makeTask(10, ['customer_id' => self::CUSTOMER]);
        $this->makeEntry(10, (int) $foreignTask->id);

        self::assertSame([
            ['customer_id' => self::CUSTOMER, 'entries' => 1, 'minutes' => 90, 'amount' => 9000, 'currency_id' => self::CURRENCY],
        ], $this->billing->customers(self::COMPANY, '2026-09-05', '2026-09-15'));
    }

    public function test_customers_is_empty_when_nothing_is_waiting_to_be_billed(): void
    {
        self::assertSame([], $this->billing->customers(self::COMPANY));
    }

    public function test_prepare_builds_one_line_per_project(): void
    {
        $this->noteSettings();
        $entries = $this->entries();

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds($this->ids($entries)), 'project');

        self::assertSame([
            ['name' => 'Website', 'description' => null, 'quantity' => 3.0, 'price' => 6000, 'discount_type' => 'fixed', 'discount' => 0, 'discount_val' => 0, 'tax' => 0, 'taxes' => [], 'total' => 18000],
            ['name' => 'Mobile app', 'description' => null, 'quantity' => 2.0, 'price' => 6000, 'discount_type' => 'fixed', 'discount' => 0, 'discount_val' => 0, 'tax' => 0, 'taxes' => [], 'total' => 12000],
            ['name' => 'No project', 'description' => null, 'quantity' => 0.75, 'price' => 6000, 'discount_type' => 'fixed', 'discount' => 0, 'discount_val' => 0, 'tax' => 0, 'taxes' => [], 'total' => 4500],
        ], $payload['items']);
        self::assertSame(34500, $payload['total']);
    }

    public function test_prepare_builds_one_line_per_member_and_names_a_leaver(): void
    {
        $this->noteSettings();
        $entries = $this->entries();
        $entries[] = $this->entry($this->landing, 99, 60, '2026-09-06');

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds($this->ids($entries)), 'member');

        self::assertSame([
            ['name' => 'Ada Lovelace', 'description' => null, 'quantity' => 3.25, 'price' => 6000, 'discount_type' => 'fixed', 'discount' => 0, 'discount_val' => 0, 'tax' => 0, 'taxes' => [], 'total' => 19500],
            ['name' => 'Grace Hopper', 'description' => null, 'quantity' => 2.5, 'price' => 6000, 'discount_type' => 'fixed', 'discount' => 0, 'discount_val' => 0, 'tax' => 0, 'taxes' => [], 'total' => 15000],
            ['name' => 'Removed member', 'description' => null, 'quantity' => 1.0, 'price' => 6000, 'discount_type' => 'fixed', 'discount' => 0, 'discount_val' => 0, 'tax' => 0, 'taxes' => [], 'total' => 6000],
        ], $payload['items']);
    }

    public function test_prepare_collapses_everything_into_one_summary_line(): void
    {
        $this->noteSettings();
        $entries = $this->entries();

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds($this->ids($entries)), 'summary');

        self::assertSame([
            ['name' => 'Time', 'description' => null, 'quantity' => 5.75, 'price' => 6000, 'discount_type' => 'fixed', 'discount' => 0, 'discount_val' => 0, 'tax' => 0, 'taxes' => [], 'total' => 34500],
        ], $payload['items']);
        self::assertSame([['entry_ids' => $this->ids($entries)]], $payload['groups']);
    }

    public function test_a_line_over_two_rates_bills_the_blended_rate(): void
    {
        $this->noteSettings();
        $first = $this->entry($this->landing, 7, 60, '2026-09-01', ['rate' => 6000, 'amount' => 6000]);
        $second = $this->entry($this->landing, 7, 30, '2026-09-02', ['rate' => 12000, 'amount' => 6000]);

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds($this->ids([$first, $second])), 'task');

        self::assertSame(
            [['name' => '#1 Landing page', 'description' => null, 'quantity' => 1.5, 'price' => 8000, 'discount_type' => 'fixed', 'discount' => 0, 'discount_val' => 0, 'tax' => 0, 'taxes' => [], 'total' => 12000]],
            $payload['items'],
        );
    }

    public function test_entry_descriptions_become_one_note_line_each(): void
    {
        $this->noteSettings('invoice_entry_descriptions');
        $first = $this->entry($this->landing, 7, 60, '2026-09-01', ['description' => 'Hero section']);
        $second = $this->entry($this->landing, 7, 60, '2026-09-02', ['description' => 'Hero section']);
        $third = $this->entry($this->landing, 7, 60, '2026-09-03', ['description' => 'Footer']);

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds($this->ids([$first, $second, $third])), 'task');

        // Two days of the same work are two days of work, not one line: the
        // note follows the time log rather than de-duplicating it.
        self::assertSame("Hero section\nHero section\nFooter", $payload['items'][0]['description']);
    }

    public function test_prepare_refuses_a_selection_spanning_two_customers(): void
    {
        $ours = $this->entry($this->landing, 7, 60, '2026-09-01');
        $theirs = $this->entry($this->task('Theirs', null, 43), 7, 60, '2026-09-02');

        $this->expectException(MixedBillingSelection::class);
        $this->expectExceptionMessage('more than one customer');

        $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds($this->ids([$ours, $theirs])), 'task');
    }

    public function test_prepare_refuses_a_selection_spanning_two_currencies(): void
    {
        $euros = $this->entry($this->landing, 7, 60, '2026-09-01');
        $pounds = $this->entry($this->landing, 7, 60, '2026-09-02', ['currency_id' => 4]);

        $this->expectException(MixedBillingSelection::class);
        $this->expectExceptionMessage('more than one currency');

        $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds($this->ids([$euros, $pounds])), 'task');
    }

    public function test_prepare_refuses_an_empty_selection(): void
    {
        $this->expectException(MixedBillingSelection::class);
        $this->expectExceptionMessage('No time entries were selected.');

        $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds([]), 'task');
    }

    public function test_prepare_refuses_an_entry_of_another_company(): void
    {
        $foreignTask = $this->makeTask(10, ['customer_id' => self::CUSTOMER]);
        $foreign = $this->makeEntry(10, (int) $foreignTask->id);

        $this->expectException(UnknownTimeEntries::class);
        $this->expectExceptionMessage("Time entries {$foreign->id} do not belong to this company.");

        $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds([(int) $foreign->id]), 'task');
    }

    public function test_prepare_refuses_non_billable_time(): void
    {
        $entry = $this->entry($this->landing, 7, 60, '2026-09-01', ['billable' => false]);

        $this->expectException(NotBillable::class);
        $this->expectExceptionMessage("Time entries {$entry->id} are not billable.");

        $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds([(int) $entry->id]), 'task');
    }

    public function test_prepare_refuses_a_timer_that_is_still_running(): void
    {
        $entry = $this->entry($this->landing, 7, 0, '2026-09-01', ['running_user_id' => 7, 'ended_at' => null]);

        $this->expectException(NotBillable::class);
        $this->expectExceptionMessage('still running');

        $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds([(int) $entry->id]), 'task');
    }

    public function test_prepare_refuses_time_that_is_already_on_a_live_invoice(): void
    {
        $entry = $this->entry($this->landing, 7, 60, '2026-09-01', ['invoice_id' => 77]);
        $this->companyData->withInvoices(self::COMPANY, 77);

        $this->expectException(EntriesAlreadyInvoiced::class);

        $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds([(int) $entry->id]), 'task');
    }

    public function test_prepare_re_bills_time_whose_invoice_vanished(): void
    {
        $entry = $this->entry($this->landing, 7, 60, '2026-09-01', ['invoice_id' => 88]);

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds([(int) $entry->id]), 'task');

        self::assertSame(6000, $payload['total']);
    }

    public function test_prepare_refuses_a_grouping_it_does_not_know(): void
    {
        $entry = $this->entry($this->landing, 7, 60, '2026-09-01');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Grouping 'weekday' is not one of task, project, member, summary.");

        $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds([(int) $entry->id]), 'weekday');
    }

    public function test_prepare_refuses_time_with_no_customer_to_bill(): void
    {
        $internal = $this->makeProject(self::COMPANY, ['name' => 'Internal', 'customer_id' => null]);
        $task = $this->task('Internal work', $internal);
        $entry = $this->entry($task, 7, 60, '2026-09-01', ['project_id' => $internal->id]);

        $this->expectException(NotBillable::class);
        $this->expectExceptionMessage('has no customer to bill');

        $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds([(int) $entry->id]), 'task');
    }

    public function test_confirm_stamps_every_entry_with_its_line(): void
    {
        Carbon::setTestNow('2026-09-15 08:00:00');
        $first = $this->entry($this->landing, 7, 60, '2026-09-01');
        $second = $this->entry($this->pricing, 7, 60, '2026-09-02');

        $stamped = $this->billing->confirm(self::COMPANY, 77, [
            ['invoice_item_id' => 101, 'entry_ids' => [(int) $first->id]],
            ['invoice_item_id' => 102, 'entry_ids' => [(int) $second->id]],
        ]);

        self::assertSame(2, $stamped);
        self::assertSame(77, $first->fresh()->invoice_id);
        self::assertSame(101, $first->fresh()->invoice_item_id);
        self::assertSame('2026-09-15 08:00:00', $first->fresh()->invoiced_at?->toDateTimeString());
        self::assertSame(102, $second->fresh()->invoice_item_id);
    }

    public function test_confirm_can_be_replayed_after_a_half_finished_round_trip(): void
    {
        $entry = $this->entry($this->landing, 7, 60, '2026-09-01');
        $items = [['invoice_item_id' => 101, 'entry_ids' => [(int) $entry->id]]];

        self::assertSame(1, $this->billing->confirm(self::COMPANY, 77, $items));
        self::assertSame(0, $this->billing->confirm(self::COMPANY, 77, $items));
        self::assertSame(77, $entry->fresh()->invoice_id);
    }

    public function test_confirm_refuses_an_entry_that_belongs_to_another_invoice(): void
    {
        $entry = $this->entry($this->landing, 7, 60, '2026-09-01', ['invoice_id' => 77, 'invoice_item_id' => 101]);

        $this->expectException(EntriesAlreadyInvoiced::class);
        $this->expectExceptionMessage("Time entry {$entry->id} is already stamped with invoice 77.");

        $this->billing->confirm(self::COMPANY, 78, [['invoice_item_id' => 201, 'entry_ids' => [(int) $entry->id]]]);
    }

    public function test_confirm_refuses_an_entry_of_another_company_and_stamps_nothing(): void
    {
        $ours = $this->entry($this->landing, 7, 60, '2026-09-01');
        $foreignTask = $this->makeTask(10);
        $foreign = $this->makeEntry(10, (int) $foreignTask->id);

        try {
            $this->billing->confirm(self::COMPANY, 77, [
                ['invoice_item_id' => 101, 'entry_ids' => [(int) $ours->id, (int) $foreign->id]],
            ]);
            self::fail('Expected the confirmation to be refused.');
        } catch (UnknownTimeEntries $exception) {
            self::assertSame("Time entries {$foreign->id} do not belong to this company.", $exception->getMessage());
        }

        self::assertNull($ours->fresh()->invoice_id);
    }

    public function test_prepare_invoices_every_unbilled_entry_of_the_named_tasks(): void
    {
        $this->noteSettings();
        $first = $this->entry($this->landing, 7, 60, '2026-09-01');
        $second = $this->entry($this->landing, 8, 30, '2026-09-02');
        $third = $this->entry($this->pricing, 7, 90, '2026-09-03');
        $this->entry($this->onboarding, 7, 60, '2026-09-04');

        $payload = $this->billing->prepare(
            self::COMPANY,
            BillingSelection::fromTaskIds([(int) $this->landing->id, (int) $this->pricing->id]),
        );

        self::assertSame(['#1 Landing page', '#2 Pricing page'], array_column($payload['items'], 'name'));
        self::assertSame([
            ['entry_ids' => [(int) $first->id, (int) $second->id]],
            ['entry_ids' => [(int) $third->id]],
        ], $payload['groups']);
        self::assertSame(18000, $payload['total']);
    }

    public function test_a_task_selection_takes_only_the_time_that_can_be_billed_today(): void
    {
        $open = $this->entry($this->landing, 7, 60, '2026-09-01');
        $this->entry($this->landing, 7, 60, '2026-09-02', ['billable' => false]);
        $this->entry($this->landing, 7, 0, '2026-09-03', ['running_user_id' => 7, 'ended_at' => null]);
        $this->entry($this->landing, 7, 60, '2026-09-04', ['invoice_id' => 77, 'invoice_item_id' => 5]);
        $orphan = $this->entry($this->landing, 7, 30, '2026-09-05', ['invoice_id' => 88, 'invoice_item_id' => 6]);
        $this->companyData->withInvoices(self::COMPANY, 77);

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromTaskIds([(int) $this->landing->id]));

        // 88 was deleted in the host, so that half hour is unbilled again.
        self::assertSame([['entry_ids' => [(int) $open->id, (int) $orphan->id]]], $payload['groups']);
        self::assertSame([['company_id' => self::COMPANY, 'invoice_ids' => [77, 88]]], $this->companyData->invoiceLookups);
    }

    public function test_a_project_selection_covers_every_task_filed_under_it(): void
    {
        $this->noteSettings();
        $this->entry($this->landing, 7, 60, '2026-09-01');
        $this->entry($this->pricing, 7, 90, '2026-09-02');
        $this->entry($this->onboarding, 7, 60, '2026-09-03');
        $this->entry($this->adHoc, 7, 45, '2026-09-04');

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromProject((int) $this->website->id));

        self::assertSame(['#1 Landing page', '#2 Pricing page'], array_column($payload['items'], 'name'));
        self::assertSame(15000, $payload['total']);
    }

    public function test_a_task_selection_with_nothing_left_to_bill_is_refused(): void
    {
        $this->entry($this->landing, 7, 60, '2026-09-01', ['billable' => false]);

        $this->expectException(NothingToInvoice::class);
        $this->expectExceptionMessage('No unbilled billable time on the selected tasks.');

        $this->billing->prepare(self::COMPANY, BillingSelection::fromTaskIds([(int) $this->landing->id]));
    }

    public function test_a_project_with_no_unbilled_time_is_refused(): void
    {
        $this->expectException(NothingToInvoice::class);
        $this->expectExceptionMessage('No unbilled billable time on the selected tasks.');

        $this->billing->prepare(self::COMPANY, BillingSelection::fromProject((int) $this->mobile->id));
    }

    public function test_a_task_selection_spanning_two_customers_names_them_in_the_refusal(): void
    {
        $theirs = $this->task('Theirs', null, 43);
        $this->entry($this->landing, 7, 60, '2026-09-01');
        $this->entry($theirs, 7, 60, '2026-09-02');

        try {
            $this->billing->prepare(
                self::COMPANY,
                BillingSelection::fromTaskIds([(int) $this->landing->id, (int) $theirs->id]),
            );
            self::fail('Expected the selection to be refused.');
        } catch (MixedBillingSelection $exception) {
            self::assertStringContainsString('more than one customer', $exception->getMessage());
            self::assertSame(['customer_ids' => [self::CUSTOMER, 43]], $exception->context());
        }
    }

    public function test_prepare_refuses_a_task_of_another_company(): void
    {
        $foreignTask = $this->makeTask(10, ['customer_id' => self::CUSTOMER]);
        $this->makeEntry(10, (int) $foreignTask->id);

        $this->expectException(ModelNotFoundException::class);

        $this->billing->prepare(self::COMPANY, BillingSelection::fromTaskIds([(int) $foreignTask->id]));
    }

    public function test_prepare_refuses_a_project_of_another_company(): void
    {
        $foreignProject = $this->makeProject(10, ['name' => 'Theirs', 'customer_id' => self::CUSTOMER]);

        $this->expectException(ModelNotFoundException::class);

        $this->billing->prepare(self::COMPANY, BillingSelection::fromProject((int) $foreignProject->id));
    }

    public function test_a_note_line_carries_exactly_the_parts_the_company_asked_for(): void
    {
        $entry = $this->entry($this->landing, 7, 90, '2026-09-01', ['description' => 'Hero section']);
        $selection = BillingSelection::fromEntryIds([(int) $entry->id]);

        $this->noteSettings();
        self::assertNull($this->billing->prepare(self::COMPANY, $selection)['items'][0]['description']);

        $this->noteSettings('invoice_entry_dates');
        self::assertSame('2026-09-01', $this->billing->prepare(self::COMPANY, $selection)['items'][0]['description']);

        $this->noteSettings('invoice_entry_times');
        self::assertSame('09:00-10:30', $this->billing->prepare(self::COMPANY, $selection)['items'][0]['description']);

        $this->noteSettings('invoice_entry_hours');
        self::assertSame('1.50 h', $this->billing->prepare(self::COMPANY, $selection)['items'][0]['description']);

        $this->noteSettings('invoice_entry_descriptions');
        self::assertSame('Hero section', $this->billing->prepare(self::COMPANY, $selection)['items'][0]['description']);

        $this->noteSettings('invoice_entry_dates', 'invoice_entry_times', 'invoice_entry_hours', 'invoice_entry_descriptions');
        self::assertSame(
            '2026-09-01  09:00-10:30  1.50 h  Hero section',
            $this->billing->prepare(self::COMPANY, $selection)['items'][0]['description'],
        );
    }

    public function test_an_entry_with_nothing_switched_on_to_show_leaves_no_note_line(): void
    {
        $this->noteSettings('invoice_entry_times', 'invoice_entry_descriptions');
        $described = $this->entry($this->landing, 7, 60, '2026-09-01', ['description' => 'Hero section']);
        $manual = $this->entry($this->landing, 7, 60, '2026-09-02', ['started_at' => null, 'ended_at' => null]);

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds($this->ids([$described, $manual])));

        self::assertSame('09:00-10:00  Hero section', $payload['items'][0]['description']);
    }

    public function test_a_note_can_open_with_the_project_name_and_the_task_description(): void
    {
        Task::query()->whereKey($this->landing->id)->update(['description' => "  Rebuild the hero  \n"]);
        $entry = $this->entry($this->landing, 7, 60, '2026-09-01');
        $selection = BillingSelection::fromEntryIds([(int) $entry->id]);

        $this->noteSettings('invoice_project_heading', 'invoice_task_description', 'invoice_entry_dates');
        self::assertSame(
            "## Website\nRebuild the hero\n2026-09-01",
            $this->billing->prepare(self::COMPANY, $selection)['items'][0]['description'],
        );

        $this->noteSettings('invoice_entry_dates');
        self::assertSame('2026-09-01', $this->billing->prepare(self::COMPANY, $selection)['items'][0]['description']);
    }

    public function test_the_task_description_never_heads_a_line_that_is_not_one_task(): void
    {
        Task::query()->whereKey($this->landing->id)->update(['description' => 'Rebuild the hero']);
        $this->noteSettings('invoice_project_heading', 'invoice_task_description', 'invoice_entry_dates');
        $entry = $this->entry($this->landing, 7, 60, '2026-09-01');

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds([(int) $entry->id]), 'member');

        self::assertSame("## Website\n2026-09-01", $payload['items'][0]['description']);
    }

    public function test_a_note_too_long_to_print_says_how_many_entries_it_left_out(): void
    {
        $this->noteSettings('invoice_entry_dates', 'invoice_entry_descriptions');
        $entries = [];
        for ($day = 1; $day <= 60; $day++) {
            $entries[] = $this->entry($this->landing, 7, 60, '2026-09-01', ['description' => str_repeat('x', 60)]);
        }

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds($this->ids($entries)));
        $description = (string) $payload['items'][0]['description'];
        $lines = explode("\n", $description);

        self::assertLessThanOrEqual(InvoiceLineComposer::MAX_LENGTH, mb_strlen($description));
        self::assertCount(28, $lines);
        self::assertSame('and 33 more entries', end($lines));
        self::assertSame('2026-09-01  '.str_repeat('x', 60), $lines[0]);
    }

    public function test_a_note_that_drops_exactly_one_entry_says_so_in_the_singular(): void
    {
        $this->noteSettings('invoice_entry_dates', 'invoice_entry_descriptions');
        $entries = [
            $this->entry($this->landing, 7, 60, '2026-09-01', ['description' => str_repeat('x', 990)]),
            $this->entry($this->landing, 7, 60, '2026-09-02', ['description' => str_repeat('x', 990)]),
        ];

        $payload = $this->billing->prepare(self::COMPANY, BillingSelection::fromEntryIds($this->ids($entries)));
        $description = (string) $payload['items'][0]['description'];
        $lines = explode("\n", $description);

        self::assertLessThanOrEqual(InvoiceLineComposer::MAX_LENGTH, mb_strlen($description));
        self::assertCount(2, $lines);
        self::assertSame('and 1 more entry', end($lines));
        self::assertSame('2026-09-01  '.str_repeat('x', 990), $lines[0]);
    }

    /**
     * Turn on exactly these line note settings, and nothing else.
     *
     * Most of what `prepare()` returns has nothing to do with the notes, so a
     * test that is about quantities or groups says so by switching every part
     * off, and a test that is about one part switches on only that one.
     */
    private function noteSettings(string ...$on): void
    {
        foreach (array_keys(ModuleSettings::FLAGS) as $key) {
            if (str_starts_with($key, 'invoice_')) {
                $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.$key, in_array($key, $on, true));
            }
        }
    }

    private function task(string $name, ?Project $project, int $customerId = self::CUSTOMER): Task
    {
        return $this->makeTask(self::COMPANY, [
            'name' => $name,
            'project_id' => $project?->id,
            'customer_id' => $project === null ? $customerId : $project->customer_id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function entry(Task $task, int $userId, int $minutes, string $day, array $attributes = []): TimeEntry
    {
        return $this->makeEntry(self::COMPANY, (int) $task->id, $attributes + [
            'project_id' => $task->project_id,
            'user_id' => $userId,
            'started_at' => Carbon::parse($day.' 09:00:00'),
            'ended_at' => Carbon::parse($day.' 09:00:00')->addMinutes($minutes),
            'duration_minutes' => $minutes,
            'rate' => self::RATE,
            'amount' => (int) round($minutes / 60 * self::RATE),
            'currency_id' => self::CURRENCY,
        ]);
    }

    /** @return list<TimeEntry> */
    private function entries(): array
    {
        return [
            $this->entry($this->landing, 7, 60, '2026-09-01'),
            $this->entry($this->landing, 8, 30, '2026-09-02'),
            $this->entry($this->pricing, 7, 90, '2026-09-03'),
            $this->entry($this->onboarding, 8, 120, '2026-09-04'),
            $this->entry($this->adHoc, 7, 45, '2026-09-05'),
        ];
    }

    /**
     * @param  list<TimeEntry>  $entries
     * @return list<int>
     */
    private function ids(array $entries): array
    {
        return array_map(static fn (TimeEntry $entry): int => (int) $entry->id, $entries);
    }
}
