<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Tests\Unit;

use InvoiceShelf\Modules\Contracts\Host\ModuleAuthorization;
use InvoiceShelf\Modules\Registry;
use Modules\AiAssistant\Application\AiToolRegistry;
use Modules\AiAssistant\Application\Tools\AiTool;
use Modules\AiAssistant\Drivers\AiDriverFactory;
use Modules\AiAssistant\Drivers\OpenRouterDriver;
use PHPUnit\Framework\TestCase;

final class AiDriverFactoryAndRegistryTest extends TestCase
{
    public function test_factory_resolves_a_registry_driver_and_rejects_unknown_drivers(): void
    {
        Registry::registerAiDriver('module_test_openrouter', [
            'class' => OpenRouterDriver::class,
            'label' => 'test.openrouter',
            'supported_roles' => ['chat', 'text_generation'],
            'suggested_models' => [],
            'config_fields' => [],
        ]);
        self::assertInstanceOf(OpenRouterDriver::class, AiDriverFactory::make('module_test_openrouter', 'key'));
        $this->expectException(\InvalidArgumentException::class);
        AiDriverFactory::make('unknown-module-driver', 'key');
    }

    public function test_tool_registry_hides_and_blocks_tools_without_host_permission(): void
    {
        $authorization = new class implements ModuleAuthorization
        {
            public bool $allowed = false;

            public function allows(int $userId, int $companyId, string $ability, ?string $resource = null): bool
            {
                return $this->allowed;
            }
        };
        $tool = new class extends AiTool
        {
            public function name(): string
            {
                return 'restricted';
            }

            public function description(): string
            {
                return 'Restricted test tool.';
            }

            public function parameterSchema(): array
            {
                return ['type' => 'object', 'properties' => (object) [], 'required' => []];
            }

            public function requiredAbility(): ?array
            {
                return ['view-invoice', 'invoice'];
            }

            public function execute(array $arguments, int $companyId, int $userId): mixed
            {
                return ['company_id' => $companyId, 'user_id' => $userId];
            }
        };
        $registry = new AiToolRegistry($authorization, [$tool]);
        self::assertSame([], $registry->schemas(4, 9));
        self::assertSame(['error' => 'unauthorized', 'message' => 'You do not have permission to access this data.'], $registry->execute('restricted', [], 9, 4));
        $authorization->allowed = true;
        self::assertCount(1, $registry->schemas(4, 9));
        self::assertSame(['company_id' => 9, 'user_id' => 4], $registry->execute('restricted', [], 9, 4));
    }
}
