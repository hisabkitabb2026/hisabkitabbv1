<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use Illuminate\Support\Carbon;
use Modules\TasksProjects\Application\ReportService;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Tests\TestCase;

final class ReportServiceTest extends TestCase
{
    private const COMPANY = 9;

    private ReportService $reports;

    private Project $website;

    private Task $billed;

    private Task $standalone;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reports = new ReportService($this->companyData);
        $this->companyData->withMember(self::COMPANY, 7, 'Ada Lovelace')->withMember(self::COMPANY, 8, 'Grace Hopper');

        $this->website = $this->makeProject(self::COMPANY, ['name' => 'Website', 'customer_id' => 42, 'currency_id' => 3]);
        $this->billed = $this->makeTask(self::COMPANY, ['name' => 'Landing page', 'project_id' => $this->website->id, 'customer_id' => 42]);
        $this->standalone = $this->makeTask(self::COMPANY, ['name' => 'Ad hoc call', 'customer_id' => 43]);

        $this->entry($this->billed, 7, '2026-09-05', 60, 6000, 3);
        $this->entry($this->billed, 8, '2026-09-06', 30, 3000, 3, ['billable' => false]);
        $this->entry($this->standalone, 7, '2026-09-07', 120, 12000, 3, ['invoice_id' => 77]);
        $this->entry($this->standalone, 7, '2026-09-08', 60, 4000, 4);
        $this->entry($this->billed, 7, '2026-10-01', 600, 60000, 3);
        $this->entry($this->billed, 7, '2026-09-09', 0, 0, 3, ['running_user_id' => 7, 'ended_at' => null]);
    }

    public function test_totals_stay_per_currency_and_keep_the_unbilled_value_apart(): void
    {
        $summary = $this->summary();

        self::assertSame([
            ['currency_id' => 3, 'minutes' => 210, 'amount' => 21000, 'billable_minutes' => 180, 'billable_amount' => 18000, 'unbilled_amount' => 6000],
            ['currency_id' => 4, 'minutes' => 60, 'amount' => 4000, 'billable_minutes' => 60, 'billable_amount' => 4000, 'unbilled_amount' => 4000],
        ], $summary['totals']);
    }

    public function test_it_splits_time_by_project_member_customer_and_the_billable_flag(): void
    {
        $summary = $this->summary();

        self::assertSame([
            [(int) $this->website->id, 'Website', 3, 90],
            [null, 'No project', 3, 120],
            [null, 'No project', 4, 60],
        ], array_map(
            static fn (array $row): array => [$row['project_id'], $row['label'], $row['currency_id'], $row['minutes']],
            $summary['by_project'],
        ));

        self::assertSame([
            [7, 'Ada Lovelace', 3, 180],
            [8, 'Grace Hopper', 3, 30],
            [7, 'Ada Lovelace', 4, 60],
        ], array_map(
            static fn (array $row): array => [$row['user_id'], $row['label'], $row['currency_id'], $row['minutes']],
            $summary['by_member'],
        ));

        self::assertSame([
            [42, 3, 90],
            [43, 3, 120],
            [43, 4, 60],
        ], array_map(
            static fn (array $row): array => [$row['customer_id'], $row['currency_id'], $row['minutes']],
            $summary['by_customer'],
        ));

        self::assertSame([
            [true, 3, 180, 18000],
            [false, 3, 30, 3000],
            [true, 4, 60, 4000],
        ], array_map(
            static fn (array $row): array => [$row['billable'], $row['currency_id'], $row['minutes'], $row['amount']],
            $summary['by_billable'],
        ));
    }

    public function test_a_viewer_without_the_ability_only_aggregates_their_own_time(): void
    {
        $summary = $this->reports->summary(self::COMPANY, '2026-09-01', '2026-09-30', 8, false);

        self::assertSame([
            ['currency_id' => 3, 'minutes' => 30, 'amount' => 3000, 'billable_minutes' => 0, 'billable_amount' => 0, 'unbilled_amount' => 0],
        ], $summary['totals']);
    }

    public function test_another_companys_time_never_appears(): void
    {
        $foreign = $this->makeTask(10, ['customer_id' => 42]);
        $this->makeEntry(10, (int) $foreign->id, ['started_at' => Carbon::parse('2026-09-05 09:00:00'), 'currency_id' => 3]);

        self::assertSame(210, $this->summary()['totals'][0]['minutes']);
    }

    /** @return array<string, mixed> */
    private function summary(): array
    {
        return $this->reports->summary(self::COMPANY, '2026-09-01', '2026-09-30', 7, true);
    }

    /** @param array<string, mixed> $attributes */
    private function entry(Task $task, int $userId, string $day, int $minutes, int $amount, int $currencyId, array $attributes = []): void
    {
        $this->makeEntry(self::COMPANY, (int) $task->id, $attributes + [
            'project_id' => $task->project_id,
            'user_id' => $userId,
            'started_at' => Carbon::parse($day.' 09:00:00'),
            'ended_at' => Carbon::parse($day.' 09:00:00')->addMinutes($minutes),
            'duration_minutes' => $minutes,
            'rate' => 6000,
            'amount' => $amount,
            'currency_id' => $currencyId,
        ]);
    }
}
