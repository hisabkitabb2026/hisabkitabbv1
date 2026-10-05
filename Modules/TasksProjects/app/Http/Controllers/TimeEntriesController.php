<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\TasksProjects\Application\TimeEntryService;
use Modules\TasksProjects\Http\Requests\ListTimeEntriesRequest;
use Modules\TasksProjects\Http\Requests\StoreTimeEntryRequest;
use Modules\TasksProjects\Http\Requests\UpdateTimeEntryRequest;
use Modules\TasksProjects\Http\Resources\TimeEntryResource;
use Modules\TasksProjects\Models\TimeEntry;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Support\CompanyContext;
use Modules\TasksProjects\Support\ModuleSettings;

/**
 * Time typed by hand.
 *
 * Everyone who may see their own time reaches this controller; what differs is
 * how far they see. Without view-all-time, and with the company setting closed,
 * the list is the caller's own rows and another member's entry is invisible.
 * Writing over someone else's entry always needs edit-all-time.
 */
final class TimeEntriesController extends Controller
{
    public function __construct(
        Authorizes $authorizes,
        private readonly TimeEntryService $entries,
        private readonly ModuleSettings $settings,
    ) {
        parent::__construct($authorizes);
    }

    public function index(ListTimeEntriesRequest $request): AnonymousResourceCollection
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_OWN_TIME);

        $filters = $request->validated();
        $entries = $this->entries->listFor(
            $context->companyId,
            array_filter([
                'user_id' => isset($filters['user_id']) ? (int) $filters['user_id'] : null,
                'project_id' => isset($filters['project_id']) ? (int) $filters['project_id'] : null,
                'task_id' => isset($filters['task_id']) ? (int) $filters['task_id'] : null,
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
                'billable' => array_key_exists('billable', $filters) ? $request->boolean('billable') : null,
                'billed' => array_key_exists('billed', $filters) ? $request->boolean('billed') : null,
            ], static fn (mixed $value): bool => $value !== null),
            $context->userId,
            $this->canSeeAllTime($context, $this->settings),
        );

        return TimeEntryResource::collection($this->paginate($entries, $request));
    }

    public function store(StoreTimeEntryRequest $request): TimeEntryResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_OWN_TIME);

        $validated = $request->validated();
        $userId = isset($validated['user_id']) ? (int) $validated['user_id'] : $context->userId;

        if ($userId !== $context->userId) {
            $this->authorize($context, Abilities::EDIT_ALL_TIME);
        }

        return new TimeEntryResource($this->entries->create($context->companyId, ['user_id' => $userId] + $validated));
    }

    public function show(Request $request, int $id): TimeEntryResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_OWN_TIME);

        $entry = $this->entries->findForCompany($context->companyId, $id);

        if ($this->belongsToSomeoneElse($context, $entry) && ! $this->canSeeAllTime($context, $this->settings)) {
            $this->authorize($context, Abilities::VIEW_ALL_TIME);
        }

        return new TimeEntryResource($entry);
    }

    public function update(UpdateTimeEntryRequest $request, int $id): TimeEntryResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_OWN_TIME);

        $this->requireWriteAccess($context, $this->entries->findForCompany($context->companyId, $id));

        return new TimeEntryResource($this->entries->update($context->companyId, $id, $request->validated()));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_OWN_TIME);

        $this->requireWriteAccess($context, $this->entries->findForCompany($context->companyId, $id));
        $this->entries->delete($context->companyId, $id);

        return response()->json(['success' => true]);
    }

    private function requireWriteAccess(CompanyContext $context, TimeEntry $entry): void
    {
        if ($this->belongsToSomeoneElse($context, $entry)) {
            $this->authorize($context, Abilities::EDIT_ALL_TIME);
        }
    }

    private function belongsToSomeoneElse(CompanyContext $context, TimeEntry $entry): bool
    {
        return (int) $entry->user_id !== $context->userId;
    }
}
