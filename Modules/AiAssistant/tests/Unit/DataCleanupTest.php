<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Tests\Unit;

use InvoiceShelf\Modules\Contracts\Host\SettingsStore;
use Modules\AiAssistant\Application\AiConfigurationService;
use Modules\AiAssistant\Lifecycle\DataCleanup;
use PHPUnit\Framework\TestCase;

final class DataCleanupTest extends TestCase
{
    public function test_it_removes_every_global_and_company_legacy_ai_setting(): void
    {
        $settings = new class implements SettingsStore
        {
            public array $global = [];

            public array $company = [];

            public array $removedCompanyKeys = [];

            public function getGlobal(string $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function putGlobal(string $key, mixed $value): void {}

            public function deleteGlobal(string $key): void
            {
                unset($this->global[$key]);
            }

            public function getCompany(int $companyId, string $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function putCompany(int $companyId, string $key, mixed $value): void {}

            public function deleteCompany(int $companyId, string $key): void {}

            public function deleteCompanyForAll(string $key): void
            {
                $this->removedCompanyKeys[] = $key;
            }
        };
        $settings->global = array_fill_keys(AiConfigurationService::SETTING_KEYS, 'value');

        (new DataCleanup($settings))->cleanup();

        self::assertSame([], $settings->global);
        self::assertContains('company_ai_api_key', $settings->removedCompanyKeys);
        self::assertContains('use_custom_ai_config', $settings->removedCompanyKeys);
    }
}
