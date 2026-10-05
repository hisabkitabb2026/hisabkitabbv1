<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\TasksProjects\Application\ProjectService;
use Modules\TasksProjects\Http\Requests\ListProjectsRequest;
use Modules\TasksProjects\Http\Requests\StoreProjectRequest;
use Modules\TasksProjects\Http\Requests\UpdateProjectRequest;
use Modules\TasksProjects\Http\Resources\ProjectResource;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;

final class ProjectsController extends Controller
{
    public function __construct(Authorizes $authorizes, private readonly ProjectService $projects)
    {
        parent::__construct($authorizes);
    }

    public function index(ListProjectsRequest $request): AnonymousResourceCollection
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_PROJECT);

        $filters = $request->validated();
        $projects = $this->projects->listFor($context->companyId, array_filter([
            'status' => $filters['status'] ?? null,
            'customer_id' => isset($filters['customer_id']) ? (int) $filters['customer_id'] : null,
            'user_id' => isset($filters['member_id']) ? (int) $filters['member_id'] : null,
            'search' => $filters['search'] ?? null,
            'sort_by' => $filters['sort_by'] ?? null,
            'sort_order' => $filters['sort_order'] ?? null,
        ], static fn (mixed $value): bool => $value !== null));

        return ProjectResource::collection($this->paginate($projects, $request));
    }

    public function store(StoreProjectRequest $request): ProjectResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::CREATE_PROJECT);

        return new ProjectResource($this->projects->create(
            $context->companyId,
            $request->validated() + ['creator_id' => $context->userId],
        ));
    }

    public function show(Request $request, int $id): ProjectResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_PROJECT);

        $project = $this->projects->findForCompany($context->companyId, $id);

        return (new ProjectResource($project))->withTotals($this->projects->totals($project));
    }

    public function update(UpdateProjectRequest $request, int $id): ProjectResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_PROJECT);

        return new ProjectResource($this->projects->update($context->companyId, $id, $request->validated()));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::DELETE_PROJECT);

        $this->projects->delete($context->companyId, $id);

        return response()->json(['success' => true]);
    }

    public function archive(Request $request, int $id): ProjectResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_PROJECT);

        return new ProjectResource($this->projects->archive($context->companyId, $id));
    }

    public function unarchive(Request $request, int $id): ProjectResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_PROJECT);

        return new ProjectResource($this->projects->unarchive($context->companyId, $id));
    }
}
