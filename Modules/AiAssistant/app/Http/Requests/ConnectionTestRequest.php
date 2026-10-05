<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\AiAssistant\Drivers\AiDriverFactory;
use Modules\AiAssistant\Rules\PublicHttpUrl;

final class ConnectionTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['ai_driver' => ['required', 'string', Rule::in(AiDriverFactory::availableDrivers())], 'ai_api_key' => ['nullable', 'string', 'max:1000'], 'ai_base_url' => ['nullable', 'url', 'max:500', new PublicHttpUrl]];
    }
}
