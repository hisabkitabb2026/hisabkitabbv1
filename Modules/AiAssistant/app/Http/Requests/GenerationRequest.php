<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['prompt' => ['required', 'string', 'max:4000'], 'context' => ['nullable', 'string', 'max:20000']];
    }
}
