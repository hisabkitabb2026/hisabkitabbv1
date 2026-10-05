<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Tests\Unit;

use InvoiceShelf\Modules\Contracts\Host\SettingsStore;
use Modules\AiAssistant\Application\AiConfigurationService;
use PHPUnit\Framework\TestCase;

final class AiConfigurationServiceTest extends TestCase
{
    public function test_it_preserves_the_legacy_setting_keys_when_saving_global_configuration(): void
    {
        $settings = new class implements SettingsStore
        {
            public array $global = [];

            public array $company = [];

            public function getGlobal(string $key, mixed $default = null): mixed
            {
                return $this->global[$key] ?? $default;
            }

            public function putGlobal(string $key, mixed $value): void
            {
                $this->global[$key] = $value;
            }

            public function deleteGlobal(string $key): void
            {
                unset($this->global[$key]);
            }

            public function getCompany(int $companyId, string $key, mixed $default = null): mixed
            {
                return $this->company[$companyId][$key] ?? $default;
            }

            public function putCompany(int $companyId, string $key, mixed $value): void
            {
                $this->company[$companyId][$key] = $value;
            }

            public function deleteCompany(int $companyId, string $key): void
            {
                unset($this->company[$companyId][$key]);
            }

            public function deleteCompanyForAll(string $key): void
            {
                foreach ($this->company as &$settings) {
                    unset($settings[$key]);
                }
            }
        };

        $service = new AiConfigurationService($settings);
        $service->saveGlobalConfig(['ai_enabled' => 'YES', 'ai_chat_enabled' => 'YES']);

        self::assertSame('YES', $settings->global['ai_enabled']);
        self::assertSame('YES', $settings->global['ai_chat_enabled']);
        self::assertArrayNotHasKey('company_ai_enabled', $settings->global);
    }
}
