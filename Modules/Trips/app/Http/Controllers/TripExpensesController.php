<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Trips\Application\TripService;
use Modules\Trips\Http\Requests\ExpenseRequest;
use Modules\Trips\Http\Resources\TripResource;
use Modules\Trips\Support\Abilities;
use Modules\Trips\Support\Authorizes;

final class TripExpensesController extends Controller
{
    public function __construct(
        Authorizes $authorizes,
        private readonly TripService $trips,
    ) {
        parent::__construct($authorizes);
    }

    public function store(ExpenseRequest $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TRIP);

        $trip = $this->trips->findForCompany($context->companyId, $id);
        $this->trips->addExpense($context->userId, $trip, $request->validated());

        return response()->json([
            'data' => new TripResource($trip->refresh()->load(['receipts', 'events', 'expenses', 'status'])),
        ], 201);
    }

    public function destroy(Request $request, int $id, int $expenseId): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TRIP);

        $trip = $this->trips->findForCompany($context->companyId, $id);
        $this->trips->removeExpense($context->userId, $trip, $expenseId);

        return response()->json([
            'data' => new TripResource($trip->refresh()->load(['receipts', 'events', 'expenses', 'status'])),
        ]);
    }
}
