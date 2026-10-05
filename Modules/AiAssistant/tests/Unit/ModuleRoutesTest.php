<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Tests\Unit;

use Illuminate\Routing\Route;
use Modules\AiAssistant\Http\Admin\AiConfigurationController;
use Modules\AiAssistant\Http\Company\CompanyAiConfigurationController;
use Orchestra\Testbench\TestCase;

final class ModuleRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require dirname(__DIR__, 2).'/routes/api.php';
    }

    public function test_configuration_controllers_load_with_compatible_signatures(): void
    {
        self::assertTrue(class_exists(AiConfigurationController::class));
        self::assertTrue(class_exists(CompanyAiConfigurationController::class));
    }

    public function test_admin_and_company_routes_use_their_respective_middleware_stacks(): void
    {
        $admin = $this->route('api/v1/ai/admin-capabilities');
        $company = $this->route('api/v1/ai/capabilities');

        self::assertContains('auth:sanctum', $admin->middleware());
        self::assertNotContains('company', $admin->middleware());
        self::assertNotContains('bouncer', $admin->middleware());

        self::assertContains('auth:sanctum', $company->middleware());
        self::assertContains('company', $company->middleware());
        self::assertContains('bouncer', $company->middleware());
    }

    private function route(string $uri): Route
    {
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            if ($route->uri() === $uri) {
                return $route;
            }
        }

        self::fail("Route {$uri} was not registered.");
    }
}
