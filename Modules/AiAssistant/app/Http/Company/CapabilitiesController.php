<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Http\Company;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\AiAssistant\Application\AiConfigurationService;
use Modules\AiAssistant\Http\ModuleController;

final class CapabilitiesController extends ModuleController
{
    public function __construct(private readonly AiConfigurationService $configuration) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorizeAbility('use ai');
        $config = $this->configuration->resolveForCompany((int) $request->header('company'));

        return response()->json([
            'enabled' => $config !== null,
            'chat' => (bool) ($config['chat_enabled'] ?? false),
            'text_generation' => (bool) ($config['text_generation_enabled'] ?? false),
            'can_manage_company' => Gate::allows('owner only'),
            'can_manage_global' => Gate::allows('manage ai config'),
        ]);
    }
}
