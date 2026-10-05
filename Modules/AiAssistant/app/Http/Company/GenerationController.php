<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Http\Company;

use Illuminate\Http\JsonResponse;
use InvoiceShelf\Modules\Ai\Exceptions\AiException;
use Modules\AiAssistant\Application\AiTextGenerationService;
use Modules\AiAssistant\Http\ModuleController;
use Modules\AiAssistant\Http\Requests\GenerationRequest;

final class GenerationController extends ModuleController
{
    public function __construct(private readonly AiTextGenerationService $generator) {}

    public function __invoke(GenerationRequest $request): JsonResponse
    {
        $this->authorizeAbility('use ai');
        $values = $request->validated();
        try {
            $text = $this->generator->generate((int) $request->header('company'), $values['prompt'], $values['context'] ?? null);
        } catch (AiException $exception) {
            return response()->json(['error' => $exception->errorKey, 'message' => $exception->getMessage()], 422);
        }

        return response()->json(['text' => $text]);
    }
}
