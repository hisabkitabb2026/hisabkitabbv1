<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Trips\Application\TripService;
use Modules\Trips\Http\Requests\CreateFromLrsRequest;
use Modules\Trips\Http\Requests\MoveTripRequest;
use Modules\Trips\Http\Requests\StoreTripRequest;
use Modules\Trips\Http\Requests\UpdateTripRequest;
use Modules\Trips\Http\Resources\TripCardResource;
use Modules\Trips\Http\Resources\TripResource;
use Modules\Trips\Support\Abilities;
use Modules\Trips\Support\Authorizes;

/**
 * The board and the trip pages.
 */
final class TripsController extends Controller
{
    public function __construct(
        Authorizes $authorizes,
        private readonly TripService $trips,
    ) {
        parent::__construct($authorizes);
    }

    /** The board: statuses and every trip card, with money. */
    public function index(Request $request): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TRIP);

        $trips = $this->trips->board($context->companyId, [
            'search' => $request->input('search'),
            'customer_id' => $request->input('customer_id') ? (int) $request->input('customer_id') : null,
            'status_id' => $request->input('status_id') ? (int) $request->input('status_id') : null,
            'unbilled_only' => $request->boolean('unbilled_only'),
        ]);

        return response()->json([
            'data' => TripCardResource::collection($trips)->toArray($request),
        ]);
    }

    public function store(StoreTripRequest $request): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::CREATE_TRIP);

        $trip = $this->trips->create($context->companyId, $context->userId, $request->validated());

        return response()->json(['data' => new TripResource($trip)], 201);
    }

    /** A trip born from existing LR receipts: the shared-load entry point. */
    public function createFromLrs(CreateFromLrsRequest $request): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::CREATE_TRIP);

        $trip = $this->trips->createFromLrInvoices($context->companyId, $context->userId, $request->validated()['invoice_ids']);

        return response()->json(['data' => new TripResource($trip)], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TRIP);

        $trip = $this->trips->findForCompany($context->companyId, $id);

        return response()->json(['data' => new TripResource($trip)]);
    }

    public function update(UpdateTripRequest $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TRIP);

        $trip = $this->trips->update(
            $context->companyId,
            $context->userId,
            $this->trips->findForCompany($context->companyId, $id),
            $request->validated(),
        );

        return response()->json(['data' => new TripResource($trip)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::DELETE_TRIP);

        $this->trips->delete($this->trips->findForCompany($context->companyId, $id));

        return response()->json(['success' => true]);
    }

    /** A board drag: status and position in one call. */
    public function move(MoveTripRequest $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TRIP);

        $validated = $request->validated();

        $trip = $this->trips->move(
            $context->companyId,
            $context->userId,
            $this->trips->findForCompany($context->companyId, $id),
            (int) $validated['status_id'],
            isset($validated['position']) ? (float) $validated['position'] : null,
        );

        return response()->json(['data' => new TripCardResource($trip)]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TRIP);

        $trip = $this->trips->cancel(
            $context->companyId,
            $context->userId,
            $this->trips->findForCompany($context->companyId, $id),
        );

        return response()->json(['data' => new TripResource($trip)]);
    }

    /**
     * Change a trip's status from the detail page. Unlike move, this returns
     * the full TripResource so the detail page refreshes in one call.
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TRIP);

        $validated = $request->validate([
            'status_id' => ['required', 'integer'],
        ]);

        $trip = $this->trips->changeStatus(
            $context->companyId,
            $context->userId,
            $this->trips->findForCompany($context->companyId, $id),
            (int) $validated['status_id'],
        );

        return response()->json(['data' => new TripResource($trip)]);
    }
}
