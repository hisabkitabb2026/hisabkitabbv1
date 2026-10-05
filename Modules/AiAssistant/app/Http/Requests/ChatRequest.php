<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['conversation_id' => ['nullable', 'integer'], 'message' => ['required', 'string', 'max:10000']];
    }
}
