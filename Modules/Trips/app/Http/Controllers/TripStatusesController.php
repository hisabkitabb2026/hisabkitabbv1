<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Trips\Application\TripStatusService;
use Modules\Trips\Http\Resources\TripStatusResource;
use Modules\Trips\Support\Abilities;
use Modules\Trips\Support\Authorizes;

final class TripStatusesController extends Controller
{
    public function __construct(
        Authorizes $authorizes,
        private readonly TripStatusService $statuses,
    ) {
        parent::__construct($authorizes);
    }

    public function index(Request $request): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TRIP);

        return response()->json([
            'data' => TripStatusResource::collection($this->statuses->listFor($context->companyId))->toArray($request),
        ]);
    }
}
