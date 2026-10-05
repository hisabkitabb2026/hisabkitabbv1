<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use Modules\TasksProjects\Tests\TestCase;

final class MigrationRollbackTest extends TestCase
{
    /** @var list<string> */
    private const TABLES = [
        'tp_projects',
        'tp_project_members',
        'tp_task_statuses',
        'tp_tasks',
        'tp_time_entries',
    ];

    public function test_every_module_table_is_created(): void
    {
        foreach (self::TABLES as $table) {
            self::assertTrue(Schema::hasTable($table), "Expected table {$table} to exist.");
        }
    }

    public function test_the_migrations_roll_back_and_forward_again(): void
    {
        $this->artisan('migrate:rollback', $this->migrationPath())->run();

        foreach (self::TABLES as $table) {
            self::assertFalse(Schema::hasTable($table), "Expected table {$table} to be dropped.");
        }

        $this->artisan('migrate', $this->migrationPath())->run();

        foreach (self::TABLES as $table) {
            self::assertTrue(Schema::hasTable($table), "Expected table {$table} to come back.");
        }
    }

    /** @return array{--path: string, --realpath: bool} */
    private function migrationPath(): array
    {
        return ['--path' => dirname(__DIR__, 2).'/database/migrations', '--realpath' => true];
    }

    public function test_the_projects_table_carries_the_columns_the_services_write(): void
    {
        self::assertTrue(Schema::hasColumns('tp_projects', [
            'company_id', 'customer_id', 'name', 'identifier', 'description', 'colour',
            'status', 'currency_id', 'default_rate', 'budget_minutes', 'due_date', 'creator_id',
        ]));

        self::assertTrue(Schema::hasColumns('tp_time_entries', [
            'company_id', 'task_id', 'project_id', 'user_id', 'started_at', 'ended_at',
            'duration_minutes', 'description', 'billable', 'rate', 'amount', 'currency_id',
            'running_user_id', 'invoice_id', 'invoice_item_id', 'invoiced_at',
        ]));
    }
}
