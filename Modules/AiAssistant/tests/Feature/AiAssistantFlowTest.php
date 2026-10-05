<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvoiceShelf\Modules\Ai\Contracts\AiDriver;
use InvoiceShelf\Modules\Ai\Data\AiChatResponse;
use InvoiceShelf\Modules\Ai\Exceptions\AiException;
use InvoiceShelf\Modules\Contracts\Host\ModuleAuthorization;
use InvoiceShelf\Modules\Contracts\Host\SettingsStore;
use InvoiceShelf\Modules\Registry;
use Modules\AiAssistant\Application\AiAssistantService;
use Modules\AiAssistant\Application\AiConfigurationService;
use Modules\AiAssistant\Application\AiTextGenerationService;
use Modules\AiAssistant\Application\AiToolRegistry;
use Modules\AiAssistant\Application\Tools\AiTool;
use Modules\AiAssistant\Http\Admin\AiConfigurationController;
use Modules\AiAssistant\Http\Admin\CapabilitiesController as AdminCapabilitiesController;
use Modules\AiAssistant\Http\Company\CapabilitiesController as CompanyCapabilitiesController;
use Modules\AiAssistant\Http\Company\CompanyAiConfigurationController;
use Modules\AiAssistant\Http\Company\ConversationController;
use Modules\AiAssistant\Http\Requests\AiConfigurationRequest;
use Modules\AiAssistant\Models\AiConversation;
use Modules\AiAssistant\Models\AiMessage;
use Orchestra\Testbench\TestCase;

final class AiAssistantFlowTest extends TestCase
{
    private MemorySettingsStore $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settings = new MemorySettingsStore;
        $this->app->instance(SettingsStore::class, $this->settings);
        $this->registerDriver();
        $this->allowAbilities();
        SequencedAiDriver::reset();
    }

    protected function tearDown(): void
    {
        unset(Registry::$drivers['ai']['ai-assistant-test']);
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/database/migrations');
    }

    public function test_chat_persists_a_complete_tool_loop_and_reuses_the_owned_conversation(): void
    {
        $this->enableChat();
        SequencedAiDriver::$chatResponses = [
            new AiChatResponse(null, [[
                'id' => 'call_customer_status',
                'name' => 'customer_status',
                'arguments' => ['customer' => 'Ada'],
            ]], 'tool_calls', ['tokens_in' => 12, 'tokens_out' => 4]),
            new AiChatResponse('Ada has no overdue invoices.', [], 'stop', ['tokens_in' => 26, 'tokens_out' => 8]),
        ];

        $assistant = new AiAssistantService(
            new AiConfigurationService($this->settings),
            new AiToolRegistry(new AllowingAuthorization, [new CustomerStatusTool]),
        );
        $conversation = $assistant->startConversation(41, 7);

        $reply = $assistant->chat($conversation, 'Does Ada have any overdue invoices?');

        self::assertSame('Ada has no overdue invoices.', $reply->content);
        self::assertSame('Does Ada have any overdue invoices?', $conversation->fresh()->title);
        self::assertSame('assistant-test-model', $conversation->fresh()->model);
        self::assertSame(4, AiMessage::query()->where('conversation_id', $conversation->id)->count());
        self::assertSame(
            ['user', 'assistant', 'tool', 'assistant'],
            AiMessage::query()->where('conversation_id', $conversation->id)->orderBy('id')->pluck('role')->all(),
        );
        self::assertSame(['customer' => 'Ada'], AiMessage::query()->where('conversation_id', $conversation->id)->where('role', 'assistant')->first()->tool_calls[0]['arguments']);
        self::assertSame(2, count(SequencedAiDriver::$chatRequests));
        self::assertSame('tool', SequencedAiDriver::$chatRequests[1]['messages'][3]['role']);
        self::assertJsonStringEqualsJsonString(
            '{"customer":"Ada","status":"clear"}',
            SequencedAiDriver::$chatRequests[1]['messages'][3]['content'],
        );
    }

    public function test_chat_never_executes_a_tool_the_host_denies(): void
    {
        $this->enableChat();
        SequencedAiDriver::$chatResponses = [
            new AiChatResponse(null, [['id' => 'call_private', 'name' => 'customer_status', 'arguments' => []]], 'tool_calls'),
            new AiChatResponse('I cannot access that customer data.'),
        ];

        $assistant = new AiAssistantService(
            new AiConfigurationService($this->settings),
            new AiToolRegistry(new DenyingAuthorization, [new CustomerStatusTool]),
        );

        $assistant->chat($assistant->startConversation(41, 7), 'Show customer data');

        self::assertJsonStringEqualsJsonString(
            '{"error":"unauthorized","message":"You do not have permission to access this data."}',
            SequencedAiDriver::$chatRequests[1]['messages'][3]['content'],
        );
    }

    public function test_conversations_are_scoped_to_the_current_company_and_user(): void
    {
        Gate::define('use ai', static fn (): bool => true);
        $owned = AiConversation::query()->create(['company_id' => 9, 'user_id' => 100, 'title' => 'Mine']);
        $otherUser = AiConversation::query()->create(['company_id' => 9, 'user_id' => 101, 'title' => 'Not mine']);
        $otherCompany = AiConversation::query()->create(['company_id' => 10, 'user_id' => 100, 'title' => 'Other company']);

        $controller = new ConversationController;
        $request = $this->requestFor(100, 9);

        self::assertSame([$owned->id], collect($controller->index($request)->getData(true)['conversations'])->pluck('id')->all());

        foreach ([$otherUser, $otherCompany] as $conversation) {
            try {
                $controller->show($request, $conversation->id);
                self::fail('A conversation outside the current owner and company scope was returned.');
            } catch (ModelNotFoundException) {
                self::assertTrue(true);
            }
        }
    }

    public function test_text_generation_uses_the_text_model_and_preserves_context(): void
    {
        $this->enableTextGeneration();
        SequencedAiDriver::$textResponse = "  Polite payment reminder.  \n";

        $text = (new AiTextGenerationService(new AiConfigurationService($this->settings)))
            ->generate(41, 'Make this more polite.', 'Payment is overdue.');

        self::assertSame('Polite payment reminder.', $text);
        self::assertSame('assistant-text-model', SequencedAiDriver::$textRequests[0]['model']);
        self::assertStringContainsString('Payment is overdue.', SequencedAiDriver::$textRequests[0]['prompt']);
        self::assertStringContainsString('Make this more polite.', SequencedAiDriver::$textRequests[0]['prompt']);
    }

    public function test_configuration_endpoints_mask_keys_and_preserve_them_on_masked_save(): void
    {
        $configuration = new AiConfigurationService($this->settings);
        $configuration->saveGlobalConfig($this->configurationPayload('global-secret'));
        $admin = new AiConfigurationController($configuration);

        self::assertSame('********', $admin->getConfig()->getData(true)['ai_api_key']);
        $admin->saveConfig($this->validatedConfigurationRequest($this->configurationPayload('********')));
        self::assertSame('global-secret', $configuration->getGlobalConfig()['ai_api_key']);

        $company = new CompanyAiConfigurationController($configuration);
        $companyPayload = array_replace($this->configurationPayload('company-secret'), ['use_custom_ai_config' => 'YES']);
        $company->saveConfig($this->validatedConfigurationRequest($companyPayload, 41));

        self::assertSame('********', $company->getConfig($this->requestFor(7, 41))->getData(true)['ai_api_key']);
        self::assertSame('company-secret', $configuration->resolveForCompany(41)['ai_api_key']);
        self::assertSame('global-secret', $configuration->resolveForCompany(42)['ai_api_key']);
    }

    public function test_capability_endpoints_keep_global_and_company_access_separate(): void
    {
        $this->enableChat();
        $configuration = new AiConfigurationService($this->settings);

        $company = (new CompanyCapabilitiesController($configuration))($this->requestFor(7, 41))->getData(true);
        $admin = (new AdminCapabilitiesController)->__invoke()->getData(true);

        self::assertSame([
            'enabled' => true,
            'chat' => true,
            'text_generation' => false,
            'can_manage_company' => true,
            'can_manage_global' => true,
        ], $company);
        self::assertTrue($admin['can_manage_global']);
        self::assertFalse($admin['enabled']);
    }

    public function test_configuration_request_rejects_private_base_urls_before_connection_attempts(): void
    {
        $request = AiConfigurationRequest::create('/', 'POST', array_replace($this->configurationPayload('secret'), [
            'ai_base_url' => 'http://127.0.0.1:11434',
        ]));
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));

        $this->expectException(ValidationException::class);
        $request->validateResolved();
    }

    /** @return array<string, string> */
    private function configurationPayload(string $apiKey): array
    {
        return [
            'ai_enabled' => 'YES',
            'ai_driver' => 'ai-assistant-test',
            'ai_api_key' => $apiKey,
            'ai_base_url' => 'https://api.example.test/v1',
            'ai_chat_enabled' => 'YES',
            'ai_chat_model' => 'assistant-test-model',
            'ai_text_generation_enabled' => 'YES',
            'ai_text_generation_model' => 'assistant-text-model',
        ];
    }

    private function enableChat(): void
    {
        $this->settings->global = array_replace($this->configurationPayload('chat-secret'), [
            'ai_text_generation_enabled' => 'NO',
        ]);
    }

    private function enableTextGeneration(): void
    {
        $this->settings->global = array_replace($this->configurationPayload('text-secret'), [
            'ai_chat_enabled' => 'NO',
        ]);
    }

    private function registerDriver(): void
    {
        Registry::registerAiDriver('ai-assistant-test', [
            'class' => SequencedAiDriver::class,
            'label' => 'AI assistant test driver',
            'supported_roles' => ['chat', 'text_generation'],
            'suggested_models' => [],
            'config_fields' => [],
        ]);
    }

    private function allowAbilities(): void
    {
        Auth::setUser(new GenericUser(['id' => 7]));
        Gate::define('manage ai config', static fn (): bool => true);
        Gate::define('owner only', static fn (): bool => true);
        Gate::define('use ai', static fn (): bool => true);
    }

    private function requestFor(int $userId, int $companyId): Request
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_COMPANY' => (string) $companyId]);
        $request->setUserResolver(static fn (): Authenticatable => new GenericUser(['id' => $userId]));

        return $request;
    }

    /** @param array<string, string> $payload */
    private function validatedConfigurationRequest(array $payload, ?int $companyId = null): AiConfigurationRequest
    {
        $server = $companyId === null ? [] : ['HTTP_COMPANY' => (string) $companyId];
        $request = AiConfigurationRequest::create('/', 'POST', $payload, [], [], $server);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->validateResolved();

        return $request;
    }
}

final class MemorySettingsStore implements SettingsStore
{
    /** @var array<string, mixed> */
    public array $global = [];

    /** @var array<int, array<string, mixed>> */
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
}

final class SequencedAiDriver extends AiDriver
{
    /** @var list<AiChatResponse> */
    public static array $chatResponses = [];

    /** @var list<array{messages: array<int, array<string, mixed>>, model: string, tools: array<int, array<string, mixed>>}> */
    public static array $chatRequests = [];

    /** @var list<array{prompt: string, model: string}> */
    public static array $textRequests = [];

    public static string $textResponse = '';

    public static function reset(): void
    {
        self::$chatResponses = [];
        self::$chatRequests = [];
        self::$textRequests = [];
        self::$textResponse = '';
    }

    public function chatCompletion(array $messages, string $model, array $tools = [], array $options = []): AiChatResponse
    {
        self::$chatRequests[] = compact('messages', 'model', 'tools');

        return array_shift(self::$chatResponses) ?? throw new AiException('No test chat response configured.');
    }

    public function textCompletion(string $prompt, string $model, array $options = []): string
    {
        self::$textRequests[] = compact('prompt', 'model');

        return self::$textResponse;
    }

    public function validateConnection(): array
    {
        return ['ok' => true];
    }
}

final class AllowingAuthorization implements ModuleAuthorization
{
    public function allows(int $userId, int $companyId, string $ability, ?string $resource = null): bool
    {
        return true;
    }
}

final class DenyingAuthorization implements ModuleAuthorization
{
    public function allows(int $userId, int $companyId, string $ability, ?string $resource = null): bool
    {
        return false;
    }
}

final class CustomerStatusTool extends AiTool
{
    public function name(): string
    {
        return 'customer_status';
    }

    public function description(): string
    {
        return 'Return the current customer status.';
    }

    public function parameterSchema(): array
    {
        return ['type' => 'object', 'properties' => ['customer' => ['type' => 'string']]];
    }

    public function requiredAbility(): ?array
    {
        return ['view-customer', 'customer'];
    }

    public function execute(array $arguments, int $companyId, int $userId): mixed
    {
        return ['customer' => $arguments['customer'] ?? null, 'status' => 'clear'];
    }
}
