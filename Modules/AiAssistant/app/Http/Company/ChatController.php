<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Http\Company;

use Illuminate\Http\JsonResponse;
use InvoiceShelf\Modules\Ai\Exceptions\AiException;
use Modules\AiAssistant\Application\AiAssistantService;
use Modules\AiAssistant\Http\ModuleController;
use Modules\AiAssistant\Http\Requests\ChatRequest;
use Modules\AiAssistant\Models\AiConversation;

final class ChatController extends ModuleController
{
    public function __construct(private readonly AiAssistantService $assistant) {}

    public function __invoke(ChatRequest $request): JsonResponse
    {
        $this->authorizeAbility('use ai');
        $values = $request->validated();
        $companyId = (int) $request->header('company');
        $userId = (int) $request->user()->getAuthIdentifier();
        $conversation = $this->conversation($values['conversation_id'] ?? null, $companyId, $userId, $values['message']);
        try {
            $message = $this->assistant->chat($conversation, $values['message']);
        } catch (AiException $exception) {
            return response()->json(['error' => $exception->errorKey, 'message' => $exception->getMessage()], 422);
        }
        $conversation->refresh();

        return response()->json(['conversation' => $conversation->only(['id', 'title', 'model', 'updated_at']), 'message' => $message->only(['id', 'role', 'content', 'created_at'])]);
    }

    private function conversation(?int $id, int $companyId, int $userId, string $message): AiConversation
    {
        $existing = $id === null ? null : AiConversation::query()->whereKey($id)->where('company_id', $companyId)->where('user_id', $userId)->first();

        return $existing ?? $this->assistant->startConversation($companyId, $userId, $message);
    }
}
