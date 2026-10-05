<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Http;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

abstract class ModuleController extends Controller
{
    protected function authorizeAbility(string $ability, mixed $arguments = []): void
    {
        Gate::authorize($ability, $arguments);
    }
}
