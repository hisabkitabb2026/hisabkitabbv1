<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use Modules\TasksProjects\Application\RateResolver;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Support\ModuleSettings;
use Modules\TasksProjects\Tests\TestCase;

final class RateResolverTest extends TestCase
{
    private const COMPANY = 9;

    private const USER = 7;

    public function test_a_task_override_beats_every_other_rate(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'default_rate', 1000);
        $project = $this->makeProject(self::COMPANY, ['default_rate' => 2000]);
        $this->makeMember(self::COMPANY, (int) $project->id, self::USER, 3000);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id, 'rate' => 4000]);

        self::assertSame(4000, $this->resolve($task->id));
    }

    public function test_the_members_rate_on_the_project_beats_the_project_default(): void
    {
        $project = $this->makeProject(self::COMPANY, ['default_rate' => 2000]);
        $this->makeMember(self::COMPANY, (int) $project->id, self::USER, 3000);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);

        self::assertSame(3000, $this->resolve($task->id));
    }

    public function test_a_member_without_a_rate_of_their_own_falls_through_to_the_project(): void
    {
        $project = $this->makeProject(self::COMPANY, ['default_rate' => 2000]);
        $this->makeMember(self::COMPANY, (int) $project->id, self::USER);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);

        self::assertSame(2000, $this->resolve($task->id));
    }

    public function test_another_members_rate_never_applies(): void
    {
        $project = $this->makeProject(self::COMPANY, ['default_rate' => 2000]);
        $this->makeMember(self::COMPANY, (int) $project->id, 8, 9000);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);

        self::assertSame(2000, $this->resolve($task->id));
    }

    public function test_a_standalone_task_falls_through_to_the_company_default(): void
    {
        $this->settings->putCompany(self::COMPANY, ModuleSettings::PREFIX.'default_rate', 1500);
        $task = $this->makeTask(self::COMPANY, ['customer_id' => 3]);

        self::assertSame(1500, $this->resolve($task->id));
    }

    public function test_it_resolves_to_zero_when_nothing_sets_a_rate(): void
    {
        $task = $this->makeTask(self::COMPANY);

        self::assertSame(0, $this->resolve($task->id));
    }

    public function test_an_unknown_user_never_picks_up_a_member_rate(): void
    {
        $project = $this->makeProject(self::COMPANY, ['default_rate' => 2000]);
        $this->makeMember(self::COMPANY, (int) $project->id, self::USER, 3000);
        $task = $this->makeTask(self::COMPANY, ['project_id' => $project->id]);

        self::assertSame(2000, $this->resolve($task->id, null));
    }

    /** Reload the task so the resolver reads the same row a request would. */
    private function resolve(int $taskId, ?int $userId = self::USER): int
    {
        $task = Task::query()->findOrFail($taskId);

        return (new RateResolver)->resolve($task, $userId, $this->moduleSettings());
    }
}
