<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Http\Company;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AiAssistant\Http\ModuleController;
use Modules\AiAssistant\Http\Requests\RenameConversationRequest;
use Modules\AiAssistant\Models\AiConversation;

final class ConversationController extends ModuleController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAbility('use ai');

        return response()->json(['conversations' => $this->owned($request)->latest('updated_at')->limit(50)->get(['id', 'title', 'model', 'updated_at', 'created_at'])]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorizeAbility('use ai');
        $conversation = $this->owned($request)->findOrFail($id);

        return response()->json(['conversation' => $conversation->only(['id', 'title', 'model', 'created_at', 'updated_at']), 'messages' => $conversation->messages()->whereIn('role', ['user', 'assistant'])->get(['id', 'role', 'content', 'created_at'])]);
    }

    public function update(RenameConversationRequest $request, int $id): JsonResponse
    {
        $this->authorizeAbility('use ai');
        $this->owned($request)->findOrFail($id)->update(['title' => $request->validated('title')]);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorizeAbility('use ai');
        $this->owned($request)->findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    private function owned(Request $request): Builder
    {
        return AiConversation::query()->where('company_id', (int) $request->header('company'))->where('user_id', (int) $request->user()->getAuthIdentifier());
    }
}
