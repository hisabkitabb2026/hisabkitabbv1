<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use Illuminate\Support\Carbon;
use Modules\TasksProjects\Demo\DemoSeeder;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TaskStatus;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Tests\TestCase;

final class DemoSeederTest extends TestCase
{
    private const COMPANY = 9;

    private const OWNER = 7;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-24 12:00:00');
        $this->companyData->withMember(self::COMPANY, self::OWNER, 'Demo Owner');
    }

    private function seedDemo(int $companyId = self::COMPANY): void
    {
        $seeder = new DemoSeeder;
        $seeder->setContainer($this->app)->__invoke(['companyId' => $companyId]);
    }

    public function test_the_demo_gets_projects_under_its_customers_with_a_board_and_unbilled_time(): void
    {
        $this->companyData->withCustomer(self::COMPANY, 31, 3)->withCustomer(self::COMPANY, 32)->withCustomer(self::COMPANY, 33);

        $this->seedDemo();

        $projects = Project::query()->forCompany(self::COMPANY)->orderBy('id')->get();
        self::assertSame([31, 32, 33], $projects->pluck('customer_id')->all());
        self::assertSame(3, $projects->first()->currency_id);

        $columns = Task::query()->forCompany(self::COMPANY)->get()
            ->groupBy('task_status_id')
            ->map->count();
        self::assertCount(4, TaskStatus::query()->forCompany(self::COMPANY)->get());
        self::assertCount(4, $columns, 'every column of the board has tasks');
        self::assertSame(12, $columns->sum());

        $entries = TimeEntry::query()->forCompany(self::COMPANY)->get();
        self::assertGreaterThan(10, $entries->count());
        self::assertTrue($entries->every(fn (TimeEntry $entry): bool => $entry->invoice_id === null && (int) $entry->user_id === self::OWNER));
        self::assertGreaterThan(0, $entries->where('billable', true)->sum('amount'));
        self::assertTrue($entries->contains('billable', false));
        self::assertTrue($entries->every(fn (TimeEntry $entry): bool => ! $entry->started_at->isWeekend() && $entry->started_at->lessThan(Carbon::now())));
    }

    public function test_a_company_without_customers_gets_internal_projects(): void
    {
        $this->seedDemo();

        self::assertSame([null, null, null], Project::query()->forCompany(self::COMPANY)->pluck('customer_id')->all());
    }

    public function test_a_company_that_already_has_projects_is_left_alone(): void
    {
        $this->seedDemo();
        $this->seedDemo();

        self::assertSame(3, Project::query()->forCompany(self::COMPANY)->count());
        self::assertSame(0, Project::query()->forCompany(self::COMPANY + 1)->count());
    }

    public function test_a_company_with_no_one_to_log_time_gets_nothing(): void
    {
        $this->seedDemo(self::COMPANY + 1);

        self::assertSame(0, Project::query()->count());
    }
}
