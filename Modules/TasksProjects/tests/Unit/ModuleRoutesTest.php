<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Tests\Unit;

use Illuminate\Routing\Route;
use Modules\TasksProjects\Tests\TestCase;

/**
 * The module's route table, guarded as a contract.
 *
 * Two things matter here. Every route sits behind the host stack, because the
 * company scoping and the ability checks both depend on it, and every route
 * sits under the module's slug prefix, so a future core route can never collide
 * with one of these.
 */
final class ModuleRoutesTest extends TestCase
{
    private const PREFIX = 'api/v1/tasks-projects';

    /** @var list<string> */
    private const MIDDLEWARE = ['api', 'auth:sanctum', 'company', 'bouncer'];

    public function test_it_registers_the_documented_route_table(): void
    {
        $routes = array_map(
            static fn (Route $route): array => [
                implode('|', array_values(array_diff($route->methods(), ['HEAD']))),
                $route->uri(),
                (string) $route->getName(),
            ],
            $this->moduleRoutes(),
        );

        self::assertSame([
            ['GET', 'api/v1/tasks-projects/projects', 'tasks-projects.projects.index'],
            ['POST', 'api/v1/tasks-projects/projects', 'tasks-projects.projects.store'],
            ['GET', 'api/v1/tasks-projects/projects/{id}', 'tasks-projects.projects.show'],
            ['PUT', 'api/v1/tasks-projects/projects/{id}', 'tasks-projects.projects.update'],
            ['DELETE', 'api/v1/tasks-projects/projects/{id}', 'tasks-projects.projects.destroy'],
            ['POST', 'api/v1/tasks-projects/projects/{id}/archive', 'tasks-projects.projects.archive'],
            ['POST', 'api/v1/tasks-projects/projects/{id}/unarchive', 'tasks-projects.projects.unarchive'],
            ['GET', 'api/v1/tasks-projects/projects/{id}/members', 'tasks-projects.project-members.index'],
            ['POST', 'api/v1/tasks-projects/projects/{id}/members', 'tasks-projects.project-members.store'],
            ['DELETE', 'api/v1/tasks-projects/projects/{id}/members/{userId}', 'tasks-projects.project-members.destroy'],
            ['GET', 'api/v1/tasks-projects/members', 'tasks-projects.members.index'],
            ['GET', 'api/v1/tasks-projects/tasks', 'tasks-projects.tasks.index'],
            ['POST', 'api/v1/tasks-projects/tasks', 'tasks-projects.tasks.store'],
            ['POST', 'api/v1/tasks-projects/tasks/bulk', 'tasks-projects.tasks.bulk'],
            ['GET', 'api/v1/tasks-projects/tasks/{id}', 'tasks-projects.tasks.show'],
            ['PUT', 'api/v1/tasks-projects/tasks/{id}', 'tasks-projects.tasks.update'],
            ['DELETE', 'api/v1/tasks-projects/tasks/{id}', 'tasks-projects.tasks.destroy'],
            ['POST', 'api/v1/tasks-projects/tasks/{id}/move', 'tasks-projects.tasks.move'],
            ['POST', 'api/v1/tasks-projects/tasks/{id}/start', 'tasks-projects.tasks.start'],
            ['POST', 'api/v1/tasks-projects/tasks/{id}/stop', 'tasks-projects.tasks.stop'],
            ['GET', 'api/v1/tasks-projects/tasks/{id}/time-log', 'tasks-projects.tasks.time-log'],
            ['GET', 'api/v1/tasks-projects/board', 'tasks-projects.board.index'],
            ['GET', 'api/v1/tasks-projects/task-statuses', 'tasks-projects.task-statuses.index'],
            ['POST', 'api/v1/tasks-projects/task-statuses', 'tasks-projects.task-statuses.store'],
            ['POST', 'api/v1/tasks-projects/task-statuses/reorder', 'tasks-projects.task-statuses.reorder'],
            ['PUT', 'api/v1/tasks-projects/task-statuses/{id}', 'tasks-projects.task-statuses.update'],
            ['DELETE', 'api/v1/tasks-projects/task-statuses/{id}', 'tasks-projects.task-statuses.destroy'],
            ['GET', 'api/v1/tasks-projects/time-entries', 'tasks-projects.time-entries.index'],
            ['POST', 'api/v1/tasks-projects/time-entries', 'tasks-projects.time-entries.store'],
            ['GET', 'api/v1/tasks-projects/time-entries/{id}', 'tasks-projects.time-entries.show'],
            ['PUT', 'api/v1/tasks-projects/time-entries/{id}', 'tasks-projects.time-entries.update'],
            ['DELETE', 'api/v1/tasks-projects/time-entries/{id}', 'tasks-projects.time-entries.destroy'],
            ['GET', 'api/v1/tasks-projects/timer', 'tasks-projects.timer.show'],
            ['DELETE', 'api/v1/tasks-projects/timer', 'tasks-projects.timer.destroy'],
            ['POST', 'api/v1/tasks-projects/timer/start', 'tasks-projects.timer.start'],
            ['POST', 'api/v1/tasks-projects/timer/stop', 'tasks-projects.timer.stop'],
            ['GET', 'api/v1/tasks-projects/billing/customers', 'tasks-projects.billing.customers'],
            ['GET', 'api/v1/tasks-projects/billing/unbilled', 'tasks-projects.billing.unbilled'],
            ['POST', 'api/v1/tasks-projects/billing/prepare', 'tasks-projects.billing.prepare'],
            ['POST', 'api/v1/tasks-projects/billing/confirm', 'tasks-projects.billing.confirm'],
            ['GET', 'api/v1/tasks-projects/reports/summary', 'tasks-projects.reports.summary'],
            ['GET', 'api/v1/tasks-projects/settings', 'tasks-projects.settings.show'],
        ], $routes);
    }

    public function test_every_route_sits_behind_the_host_middleware_stack(): void
    {
        foreach ($this->moduleRoutes() as $route) {
            foreach (self::MIDDLEWARE as $middleware) {
                self::assertContains(
                    $middleware,
                    $route->middleware(),
                    "Route {$route->uri()} is missing the {$middleware} middleware.",
                );
            }
        }
    }

    public function test_every_route_is_named_and_prefixed_by_the_module_slug(): void
    {
        foreach ($this->moduleRoutes() as $route) {
            self::assertStringStartsWith('tasks-projects.', (string) $route->getName());
        }
    }

    public function test_no_controller_of_the_module_is_reachable_outside_the_slug_prefix(): void
    {
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            $action = $route->getAction('controller');

            if (! is_string($action) || ! str_starts_with($action, 'Modules\\TasksProjects\\')) {
                continue;
            }

            self::assertStringStartsWith(self::PREFIX.'/', $route->uri());
        }
    }

    /** @return list<Route> */
    private function moduleRoutes(): array
    {
        $routes = [];

        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            if (str_starts_with($route->uri(), self::PREFIX)) {
                $routes[] = $route;
            }
        }

        self::assertNotSame([], $routes, 'The module registered no routes at all.');

        return $routes;
    }
}
