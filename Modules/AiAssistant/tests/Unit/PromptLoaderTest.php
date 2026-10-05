<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Tests\Unit;

use Modules\AiAssistant\Prompting\PromptLoader;
use PHPUnit\Framework\TestCase;

final class PromptLoaderTest extends TestCase
{
    public function test_it_loads_and_substitutes_prompt_placeholders(): void
    {
        $prompt = PromptLoader::load('chat-system', ['user_name' => 'Ada', 'company_name' => 'Acme', 'today' => '2026-08-05']);
        self::assertStringContainsString('Ada', $prompt);
        self::assertStringContainsString('Acme', $prompt);
        self::assertStringContainsString('2026-08-05', $prompt);
    }
}
