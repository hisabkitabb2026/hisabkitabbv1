<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Modules\TasksProjects\Application\Exceptions\TasksProjectsException;
use Modules\TasksProjects\Application\TaskService;
use Modules\TasksProjects\Http\DomainExceptionRenderer;
use Modules\TasksProjects\Http\Requests\BulkTasksRequest;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;

/**
 * The two things a task selection can do to every task at once.
 *
 * Moving a selection to a status and deleting it are safe in bulk because each
 * task answers for itself: one task that is locked, invoiced or already gone
 * lands in `failed` with its reason and the rest still go through, each in its
 * own transaction. The caller gets both lists and can show exactly what did not
 * happen.
 *
 * Starting and stopping timers are deliberately not bulk actions: one running
 * timer per user is a hard invariant of this module, so "start these twelve
 * tasks" has no meaning that is not a lie. Invoicing is not here either, for a
 * different reason: it creates one host document out of the whole selection
 * rather than doing the same thing to each task, and it has to refuse mixed
 * customers as a single failure. It lives in the billing endpoints, where that
 * all-or-nothing shape belongs.
 */
final class BulkTasksController extends Controller
{
    public const ACTION_STATUS = 'status';

    public const ACTION_DELETE = 'delete';

    /** @var list<string> */
    public const ACTIONS = [self::ACTION_STATUS, self::ACTION_DELETE];

    public function __construct(Authorizes $authorizes, private readonly TaskService $tasks)
    {
        parent::__construct($authorizes);
    }

    public function __invoke(BulkTasksRequest $request): JsonResponse
    {
        $context = $this->context($request);
        $validated = $request->validated();
        $action = (string) $validated['action'];

        $this->authorize(
            $context,
            $action === self::ACTION_DELETE ? Abilities::DELETE_TASK : Abilities::EDIT_TASK,
        );

        $updated = [];
        $failed = [];

        foreach (array_map(intval(...), $validated['ids']) as $taskId) {
            try {
                $action === self::ACTION_DELETE
                    ? $this->tasks->delete($context->companyId, $taskId)
                    : $this->tasks->move($context->companyId, $taskId, (int) $validated['task_status_id']);

                $updated[] = $taskId;
            } catch (ModelNotFoundException) {
                $failed[] = ['id' => $taskId, 'reason' => 'not_found'];
            } catch (TasksProjectsException $exception) {
                $failed[] = ['id' => $taskId, 'reason' => DomainExceptionRenderer::errorKey($exception)];
            }
        }

        return response()->json(['updated' => $updated, 'failed' => $failed]);
    }
}
