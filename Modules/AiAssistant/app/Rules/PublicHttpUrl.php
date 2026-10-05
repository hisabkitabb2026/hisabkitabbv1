<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\AiAssistant\Support\Net\PrivateNetworkGuard;

final class PublicHttpUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && trim($value) !== '' && PrivateNetworkGuard::blockedReason($value) !== null) {
            $fail('The :attribute must be a publicly reachable URL, not a private or reserved address.');
        }
    }
}
