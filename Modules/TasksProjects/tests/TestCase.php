<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;
use InvoiceShelf\Modules\Contracts\Host\ModuleAuthorization;
use InvoiceShelf\Modules\Contracts\Host\SettingsStore;
use InvoiceShelf\Modules\InvoiceShelfModulesServiceProvider;
use InvoiceShelf\Modules\Registry;
use Modules\TasksProjects\Application\BoardOrderingService;
use Modules\TasksProjects\Application\ProjectService;
use Modules\TasksProjects\Application\TaskLock;
use Modules\TasksProjects\Application\TaskNumberSequence;
use Modules\TasksProjects\Application\TaskService;
use Modules\TasksProjects\Application\TaskStatusService;
use Modules\TasksProjects\Application\TaskTimeSummary;
use Modules\TasksProjects\Http\DomainExceptionRenderer;
use Modules\TasksProjects\Models\Project;
use Modules\TasksProjects\Models\ProjectMember;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Models\TaskStatus;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\ModuleSettings;
use Modules\TasksProjects\Tests\Support\MemoryCompanyDataReader;
use Modules\TasksProjects\Tests\Support\MemorySettingsStore;
use Modules\TasksProjects\Tests\Support\RecordingAuthorization;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** The user every request acts as until a test says otherwise. */
    public const DEFAULT_USER = 7;

    protected MemorySettingsStore $settings;

    protected MemoryCompanyDataReader $companyData;

    protected RecordingAuthorization $authorization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = new MemorySettingsStore;
        $this->companyData = new MemoryCompanyDataReader;
        $this->authorization = new RecordingAuthorization;

        $this->app->instance(SettingsStore::class, $this->settings);
        $this->app->instance(CompanyDataReader::class, $this->companyData);
        $this->app->instance(ModuleAuthorization::class, $this->authorization);

        $handler = $this->app->make(ExceptionHandler::class);
        if ($handler instanceof Handler) {
            DomainExceptionRenderer::register($handler);
        }

        // `auth:sanctum`, `company` and `bouncer` are host middleware and do not
        // exist here, so the harness stands in for all three: it authenticates
        // the caller itself and puts the company on the request as a header.
        $this->withoutMiddleware();
        $this->actingAsUser(self::DEFAULT_USER);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Registry::flush();

        parent::tearDown();
    }

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [InvoiceShelfModulesServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        // SQLite in memory by default; CI also runs the suite on PostgreSQL,
        // which refuses SQL that SQLite lets through (DB_CONNECTION=pgsql).
        $app['config']->set('database.connections.testing', getenv('DB_CONNECTION') === 'pgsql'
            ? [
                'driver' => 'pgsql',
                'host' => getenv('DB_HOST') ?: '127.0.0.1',
                'port' => (int) (getenv('DB_PORT') ?: 5432),
                'database' => getenv('DB_DATABASE') ?: 'testing',
                'username' => getenv('DB_USERNAME') ?: 'postgres',
                'password' => getenv('DB_PASSWORD') ?: '',
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ]
            : [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]);
    }

    /**
     * Load the module's own translations, the way its provider does in the host.
     *
     * The harness boots the modules SDK rather than the module's provider, so
     * without this any module code that resolves a `tasksprojects::` line would
     * answer with the key and a test would never notice.
     */
    protected function defineEnvironment($app): void
    {
        $app['translator']->addNamespace('tasksprojects', dirname(__DIR__).'/lang');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__).'/database/migrations');
    }

    protected function moduleSettings(): ModuleSettings
    {
        return new ModuleSettings($this->settings);
    }

    /** A TaskService wired with the collaborators the container gives it. */
    protected function taskService(?TaskStatusService $statuses = null): TaskService
    {
        return new TaskService(
            new TaskNumberSequence,
            new BoardOrderingService,
            $statuses ?? new TaskStatusService,
            new ProjectService($this->companyData),
            new TaskLock($this->moduleSettings(), new TaskTimeSummary),
        );
    }

    /**
     * Load the module's own route file, the way the provider does in the host.
     *
     * @param  Router  $router
     */
    protected function defineRoutes($router): void
    {
        require dirname(__DIR__).'/routes/api.php';
    }

    /** Authenticate the caller without a host user model. */
    protected function actingAsUser(int $userId): static
    {
        Auth::setUser(new GenericUser(['id' => $userId]));

        return $this;
    }

    /** Send the `company` header the host middleware would have set. */
    protected function asCompany(int $companyId): static
    {
        return $this->withHeader('company', (string) $companyId);
    }

    /** @param array<string, mixed> $attributes */
    protected function makeStatus(int $companyId, array $attributes = []): TaskStatus
    {
        return TaskStatus::query()->create($attributes + [
            'company_id' => $companyId,
            'name' => 'Backlog',
            'position' => 1,
            'is_default' => true,
            'is_closed' => false,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    protected function makeProject(int $companyId, array $attributes = []): Project
    {
        return Project::query()->create($attributes + [
            'company_id' => $companyId,
            'name' => 'Website redesign',
            'status' => Project::STATUS_ACTIVE,
        ]);
    }

    protected function makeMember(int $companyId, int $projectId, int $userId, ?int $rate = null): ProjectMember
    {
        return ProjectMember::query()->create([
            'company_id' => $companyId,
            'project_id' => $projectId,
            'user_id' => $userId,
            'rate' => $rate,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    protected function makeTask(int $companyId, array $attributes = []): Task
    {
        $statusId = $attributes['task_status_id'] ?? $this->makeStatus($companyId)->id;
        unset($attributes['task_status_id']);

        return Task::query()->create($attributes + [
            'company_id' => $companyId,
            'task_status_id' => $statusId,
            'number' => (int) Task::query()->forCompany($companyId)->max('number') + 1,
            'name' => 'Build the landing page',
            'billable' => true,
            'board_position' => '1024.0000000000',
        ]);
    }

    /** @param array<string, mixed> $attributes */
    protected function makeEntry(int $companyId, int $taskId, array $attributes = []): TimeEntry
    {
        return TimeEntry::query()->create($attributes + [
            'company_id' => $companyId,
            'task_id' => $taskId,
            'user_id' => 7,
            'started_at' => Carbon::parse('2026-09-01 09:00:00'),
            'ended_at' => Carbon::parse('2026-09-01 10:00:00'),
            'duration_minutes' => 60,
            'billable' => true,
            'rate' => 10000,
            'amount' => 10000,
        ]);
    }
}
