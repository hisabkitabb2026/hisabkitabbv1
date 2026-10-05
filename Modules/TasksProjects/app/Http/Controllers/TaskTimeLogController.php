<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\TasksProjects\Application\TaskService;
use Modules\TasksProjects\Application\TimeEntryService;
use Modules\TasksProjects\Http\Resources\TimeEntryResource;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Support\ModuleSettings;

/**
 * Everything logged against one task, newest first, running clocks on top.
 *
 * Totals are open to anyone who may see the task, but the rows behind them
 * follow the same visibility rule as the timesheet: without view-all-time, and
 * with the company setting closed, the caller sees their own time and nobody
 * else's. The list is capped rather than paged, because it feeds a grid on the
 * task page and a task with five hundred entries is already a reporting
 * question rather than an editing one.
 */
final class TaskTimeLogController extends Controller
{
    public function __construct(
        Authorizes $authorizes,
        private readonly TaskService $tasks,
        private readonly TimeEntryService $entries,
        private readonly ModuleSettings $settings,
    ) {
        parent::__construct($authorizes);
    }

    public function __invoke(Request $request, int $id): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TASK);

        $task = $this->tasks->findForCompany($context->companyId, $id);

        $entries = $this->entries->logForTask(
            $context->companyId,
            (int) $task->id,
            $context->userId,
            $this->canSeeAllTime($context, $this->settings),
        );

        return response()->json([
            'data' => TimeEntryResource::collection($entries)->resolve($request),
        ]);
    }
}
