<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\TasksProjects\Application\TaskStatusService;
use Modules\TasksProjects\Http\Requests\ReorderTaskStatusesRequest;
use Modules\TasksProjects\Http\Requests\StoreTaskStatusRequest;
use Modules\TasksProjects\Http\Requests\UpdateTaskStatusRequest;
use Modules\TasksProjects\Http\Resources\TaskStatusResource;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;

/**
 * The board columns of one company.
 *
 * The list is never paged: it is the board's own configuration, the editor
 * reorders all of it at once, and four rows is the normal size.
 */
final class TaskStatusesController extends Controller
{
    public function __construct(Authorizes $authorizes, private readonly TaskStatusService $statuses)
    {
        parent::__construct($authorizes);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TASK);

        $this->statuses->ensureDefaults($context->companyId);

        return TaskStatusResource::collection($this->statuses->listFor($context->companyId));
    }

    public function store(StoreTaskStatusRequest $request): TaskStatusResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::MANAGE_TASK_STATUS);

        return new TaskStatusResource($this->statuses->create($context->companyId, $request->validated()));
    }

    public function update(UpdateTaskStatusRequest $request, int $id): TaskStatusResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::MANAGE_TASK_STATUS);

        return new TaskStatusResource($this->statuses->update($context->companyId, $id, $request->validated()));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::MANAGE_TASK_STATUS);

        $this->statuses->delete($context->companyId, $id);

        return response()->json(['success' => true]);
    }

    /** Apply the wanted column order; anything left out follows the listed ids. */
    public function reorder(ReorderTaskStatusesRequest $request): AnonymousResourceCollection
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::MANAGE_TASK_STATUS);

        $ids = array_map(intval(...), $request->validated()['ids']);
        $this->statuses->reorder($context->companyId, $ids);

        return TaskStatusResource::collection($this->statuses->listFor($context->companyId));
    }
}
