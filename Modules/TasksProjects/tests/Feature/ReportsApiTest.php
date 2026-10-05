<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Feature;

use Illuminate\Support\Carbon;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Tests\TestCase;

final class ReportsApiTest extends TestCase
{
    private const COMPANY = 9;

    private const OTHER_COMPANY = 10;

    private const CUSTOMER = 42;

    private const OTHER_USER = 8;

    private Project $website;

    private Task $landing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyData
            ->withMember(self::COMPANY, self::DEFAULT_USER, 'Ada Lovelace')
            ->withMember(self::COMPANY, self::OTHER_USER, 'Grace Hopper');

        $this->website = $this->makeProject(self::COMPANY, [
            'name' => 'Website',
            'customer_id' => self::CUSTOMER,
            'currency_id' => 3,
        ]);
        $this->landing = $this->makeTask(self::COMPANY, [
            'name' => 'Landing page',
            'project_id' => $this->website->id,
            'customer_id' => self::CUSTOMER,
        ]);
    }

    public function test_the_summary_totals_per_currency_and_splits_billable_from_the_rest(): void
    {
        $this->entry(60, '2026-09-01', 6000);
        $this->entry(30, '2026-09-02', 3000, ['invoice_id' => 77, 'invoice_item_id' => 101]);
        $this->entry(45, '2026-09-03', 0, ['billable' => false]);
        $this->entry(60, '2026-09-04', 9000, ['currency_id' => 4]);

        $response = $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/reports/summary?from=2026-09-01&to=2026-09-30');

        $response->assertOk();
        $response->assertJsonPath('data.from', '2026-09-01');
        $response->assertJsonPath('data.to', '2026-09-30');
        $response->assertJsonPath('data.totals', [
            [
                'currency_id' => 3,
                'minutes' => 135,
                'amount' => 9000,
                'billable_minutes' => 90,
                'billable_amount' => 9000,
                'unbilled_amount' => 6000,
            ],
            [
                'currency_id' => 4,
                'minutes' => 60,
                'amount' => 9000,
                'billable_minutes' => 60,
                'billable_amount' => 9000,
                'unbilled_amount' => 9000,
            ],
        ]);
        $response->assertJsonPath('data.by_billable.0.billable', true);
        $response->assertJsonPath('data.by_billable.0.minutes', 90);
        $response->assertJsonPath('data.by_billable.1.billable', false);
        $response->assertJsonPath('data.by_billable.1.minutes', 45);
    }

    public function test_the_summary_breaks_down_by_project_member_and_customer(): void
    {
        $this->entry(60, '2026-09-01', 6000);
        $this->entry(120, '2026-09-02', 12000, ['user_id' => self::OTHER_USER]);

        $response = $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/reports/summary?from=2026-09-01&to=2026-09-30');

        $response->assertJsonPath('data.by_project.0.label', 'Website');
        $response->assertJsonPath('data.by_project.0.minutes', 180);
        $response->assertJsonPath('data.by_member.0.label', 'Ada Lovelace');
        $response->assertJsonPath('data.by_member.1.label', 'Grace Hopper');
        $response->assertJsonPath('data.by_customer.0.customer_id', self::CUSTOMER);
        $response->assertJsonPath('data.by_customer.0.minutes', 180);
    }

    public function test_the_range_leaves_out_what_falls_outside_it(): void
    {
        $this->entry(60, '2026-08-31', 6000);
        $this->entry(30, '2026-09-10', 3000);
        $this->entry(60, '2026-10-01', 6000);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/reports/summary?from=2026-09-01&to=2026-09-30')
            ->assertJsonPath('data.totals.0.minutes', 30);
    }

    public function test_the_range_defaults_to_the_current_month(): void
    {
        Carbon::setTestNow('2026-09-15 12:00:00');
        $this->entry(60, '2026-08-20', 6000);
        $this->entry(30, '2026-09-10', 3000);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/reports/summary')
            ->assertOk()
            ->assertJsonPath('data.from', '2026-09-01')
            ->assertJsonPath('data.to', '2026-09-15')
            ->assertJsonPath('data.totals.0.minutes', 30);
    }

    public function test_a_viewer_with_only_their_own_time_reports_on_their_own_time(): void
    {
        $this->entry(60, '2026-09-01', 6000);
        $this->entry(120, '2026-09-02', 12000, ['user_id' => self::OTHER_USER]);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/reports/summary?from=2026-09-01&to=2026-09-30')
            ->assertJsonPath('data.totals.0.minutes', 180);

        $this->authorization->deny(Authorizes::id(Abilities::VIEW_ALL_TIME));

        $response = $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/reports/summary?from=2026-09-01&to=2026-09-30');

        $response->assertJsonPath('data.totals.0.minutes', 60);
        $response->assertJsonPath('data.by_member', [[
            'user_id' => self::DEFAULT_USER,
            'label' => 'Ada Lovelace',
            'currency_id' => 3,
            'minutes' => 60,
            'amount' => 6000,
            'billable_minutes' => 60,
            'billable_amount' => 6000,
            'unbilled_amount' => 6000,
        ]]);
    }

    public function test_the_summary_never_reaches_another_company(): void
    {
        $this->entry(60, '2026-09-01', 6000);
        $foreign = $this->makeTask(self::OTHER_COMPANY);
        $this->makeEntry(self::OTHER_COMPANY, (int) $foreign->id, ['duration_minutes' => 600, 'amount' => 60000]);

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/reports/summary?from=2026-09-01&to=2026-09-30')
            ->assertJsonPath('data.totals.0.minutes', 60);
    }

    public function test_the_summary_needs_the_own_time_ability(): void
    {
        $this->authorization->deny(Authorizes::id(Abilities::VIEW_OWN_TIME));

        $this->asCompany(self::COMPANY)
            ->getJson('/api/v1/tasks-projects/reports/summary')
            ->assertForbidden();
    }

    /** @param array<string, mixed> $attributes */
    private function entry(int $minutes, string $day, int $amount, array $attributes = []): TimeEntry
    {
        return $this->makeEntry(self::COMPANY, (int) $this->landing->id, $attributes + [
            'project_id' => $this->website->id,
            'user_id' => self::DEFAULT_USER,
            'started_at' => Carbon::parse($day.' 09:00:00'),
            'ended_at' => Carbon::parse($day.' 09:00:00')->addMinutes($minutes),
            'duration_minutes' => $minutes,
            'rate' => 6000,
            'amount' => $amount,
            'currency_id' => 3,
        ]);
    }
}
