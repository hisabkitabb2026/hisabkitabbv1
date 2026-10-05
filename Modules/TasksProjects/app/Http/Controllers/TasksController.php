<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\TasksProjects\Application\Exceptions\TimerAlreadyRunning;
use Modules\TasksProjects\Application\TaskService;
use Modules\TasksProjects\Application\TaskTimeSummary;
use Modules\TasksProjects\Application\TimerService;
use Modules\TasksProjects\Http\Requests\ListTasksRequest;
use Modules\TasksProjects\Http\Requests\MoveTaskRequest;
use Modules\TasksProjects\Http\Requests\StoreTaskRequest;
use Modules\TasksProjects\Http\Requests\UpdateTaskRequest;
use Modules\TasksProjects\Http\Resources\TaskResource;
use Modules\TasksProjects\Models\Task;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Support\CompanyContext;
use Modules\TasksProjects\Support\ModuleSettings;

/**
 * Tasks, each answering with the time logged against it.
 *
 * Every response here carries the task's `time` block, filled in for the whole
 * page at once by TaskTimeSummary so a list of fifty tasks still costs three
 * queries rather than fifty.
 */
final class TasksController extends Controller
{
    public function __construct(
        Authorizes $authorizes,
        private readonly TaskService $tasks,
        private readonly TaskTimeSummary $summary,
        private readonly TimerService $timer,
        private readonly ModuleSettings $settings,
    ) {
        parent::__construct($authorizes);
    }

    public function index(ListTasksRequest $request): AnonymousResourceCollection
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TASK);

        $filters = $request->validated();
        $tasks = $this->tasks->listFor($context->companyId, array_filter([
            'project_id' => isset($filters['project_id']) ? (int) $filters['project_id'] : null,
            'assignee_id' => isset($filters['assignee_id']) ? (int) $filters['assignee_id'] : null,
            'task_status_id' => isset($filters['task_status_id']) ? (int) $filters['task_status_id'] : null,
            'customer_id' => isset($filters['customer_id']) ? (int) $filters['customer_id'] : null,
            'invoiced' => array_key_exists('invoiced', $filters) ? $request->boolean('invoiced') : null,
            'due_before' => $filters['due_before'] ?? null,
            'due_after' => $filters['due_after'] ?? null,
            'search' => $filters['search'] ?? null,
            'sort_by' => $filters['sort_by'] ?? null,
            'sort_order' => $filters['sort_order'] ?? null,
        ], static fn (mixed $value): bool => $value !== null));

        $page = $this->paginate($tasks, $request);
        $this->summary->attach($context->companyId, $page->getCollection());

        return TaskResource::collection($page);
    }

    /**
     * Create a task, and start its creator's clock when the company asks for it.
     *
     * Auto-start is a convenience, never a precondition: a creator who is
     * already timing something else keeps that timer and still gets the task.
     */
    public function store(StoreTaskRequest $request): TaskResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::CREATE_TASK);

        $task = $this->tasks->create(
            $context->companyId,
            $request->validated() + ['creator_id' => $context->userId],
        );

        $this->autoStart($context, (int) $task->id);

        return $this->withTime($context, $task);
    }

    public function show(Request $request, int $id): TaskResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TASK);

        return $this->withTime($context, $this->tasks->findForCompany($context->companyId, $id));
    }

    public function update(UpdateTaskRequest $request, int $id): TaskResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TASK);

        return $this->withTime($context, $this->tasks->update($context->companyId, $id, $request->validated()));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::DELETE_TASK);

        $this->tasks->delete($context->companyId, $id);

        return response()->json(['success' => true]);
    }

    /** Drop a task between two neighbours of the target column. */
    public function move(MoveTaskRequest $request, int $id): TaskResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::EDIT_TASK);

        $validated = $request->validated();

        return $this->withTime($context, $this->tasks->move(
            $context->companyId,
            $id,
            (int) $validated['task_status_id'],
            isset($validated['before_id']) ? (int) $validated['before_id'] : null,
            isset($validated['after_id']) ? (int) $validated['after_id'] : null,
        ));
    }

    /** One task, with its time block filled in. */
    private function withTime(CompanyContext $context, Task $task): TaskResource
    {
        $this->summary->attach($context->companyId, [$task]);

        return new TaskResource($task);
    }

    /**
     * Start the creator's timer on a brand new task.
     *
     * The setting only ever adds a timer: a creator who already has one running
     * keeps it, and a race that slips past the check is caught by the same
     * unique index the timer relies on, so the create never fails over this.
     */
    private function autoStart(CompanyContext $context, int $taskId): void
    {
        if (! $this->settings->autoStartTasks($context->companyId)) {
            return;
        }

        if (! $this->allows($context, Abilities::VIEW_OWN_TIME)) {
            return;
        }

        if ($this->timer->running($context->companyId, $context->userId) !== null) {
            return;
        }

        try {
            $this->timer->start($context->companyId, $context->userId, $taskId);
        } catch (TimerAlreadyRunning) {
            // Another tab won the race; that timer is as good as this one.
        }
    }
}
