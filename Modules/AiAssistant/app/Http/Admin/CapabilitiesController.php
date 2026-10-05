<?php

declare(strict_types=1);

namespace Modules\AiAssistant\Http\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Modules\AiAssistant\Http\ModuleController;

final class CapabilitiesController extends ModuleController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'enabled' => false,
            'chat' => false,
            'text_generation' => false,
            'can_manage_company' => false,
            'can_manage_global' => Gate::allows('manage ai config'),
        ]);
    }
}
