<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Tests\Unit;

use InvoiceShelf\Modules\Ai\Exceptions\AiException;
use Modules\AiAssistant\Drivers\OpenRouterDriver;
use PHPUnit\Framework\TestCase;

final class OpenRouterDriverTest extends TestCase
{
    public function test_private_base_url_is_rejected_before_an_http_request(): void
    {
        $driver = new OpenRouterDriver('test-key', ['base_url' => 'http://169.254.169.254']);
        try {
            $driver->validateConnection();
            self::fail('Expected invalid base URL error.');
        } catch (AiException $exception) {
            self::assertSame('invalid_base_url', $exception->errorKey);
        }
    }

    public function test_it_parses_a_tool_call_response(): void
    {
        $driver = new class('key') extends OpenRouterDriver
        {
            public function parse(array $body): mixed
            {
                return $this->parseChatResponse($body);
            }
        };
        $response = $driver->parse(['model' => 'model', 'choices' => [['finish_reason' => 'tool_calls', 'message' => ['content' => null, 'tool_calls' => [['id' => 'call_1', 'function' => ['name' => 'search_invoices', 'arguments' => '{"limit":5}']]]]]], 'usage' => ['prompt_tokens' => 11, 'completion_tokens' => 7]]);
        self::assertTrue($response->hasToolCalls());
        self::assertSame('search_invoices', $response->toolCalls[0]['name']);
        self::assertSame(5, $response->toolCalls[0]['arguments']['limit']);
        self::assertSame(['tokens_in' => 11, 'tokens_out' => 7], $response->usage);
    }
}
