<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\TasksProjects\Application\ProjectMemberService;
use Modules\TasksProjects\Http\Requests\AttachProjectMemberRequest;
use Modules\TasksProjects\Http\Resources\ProjectMemberResource;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;

/**
 * Who works on a project, and at what rate.
 *
 * Detaching a member never touches their time entries: the history of the work
 * survives the leaver.
 */
final class ProjectMembersController extends Controller
{
    public function __construct(Authorizes $authorizes, private readonly ProjectMemberService $members)
    {
        parent::__construct($authorizes);
    }

    public function index(Request $request, int $id): AnonymousResourceCollection
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_PROJECT);

        return ProjectMemberResource::collection($this->members->listFor($context->companyId, $id));
    }

    public function store(AttachProjectMemberRequest $request, int $id): ProjectMemberResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_PROJECT);

        $validated = $request->validated();

        return new ProjectMemberResource($this->members->attach(
            $context->companyId,
            $id,
            (int) $validated['user_id'],
            isset($validated['rate']) ? (int) $validated['rate'] : null,
        ));
    }

    public function destroy(Request $request, int $id, int $userId): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_PROJECT);

        $this->members->detach($context->companyId, $id, $userId);

        return response()->json(['success' => true]);
    }
}
