<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\TasksProjects\Application\TimerService;
use Modules\TasksProjects\Http\Requests\StartTaskTimerRequest;
use Modules\TasksProjects\Http\Requests\StartTimerRequest;
use Modules\TasksProjects\Http\Requests\StopTimerRequest;
use Modules\TasksProjects\Http\Resources\TimeEntryResource;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;

/**
 * The caller's own running timer, one per company.
 *
 * A start on another task while one is running is a conflict rather than a
 * validation error, because the first timer is still perfectly valid; the UI
 * offers to stop it. A start on the task already being timed is not a conflict
 * at all: it applies the details it carries and answers the running entry.
 *
 * The same timer is reachable two ways. `timer/start` and `timer/stop` name the
 * task in the body and are what the timesheet and the header chip use; the
 * `tasks/{id}/start` and `tasks/{id}/stop` pair below addresses the task in the
 * URL, which is what a play button on a row or a card needs: it stops that task
 * or nothing at all, so a stale row can never stop a clock the user has since
 * moved elsewhere.
 */
final class TimerController extends Controller
{
    public function __construct(Authorizes $authorizes, private readonly TimerService $timer)
    {
        parent::__construct($authorizes);
    }

    /** The running entry, or a null payload when the clock is not running. */
    public function show(Request $request): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_OWN_TIME);

        $entry = $this->timer->running($context->companyId, $context->userId);

        return response()->json([
            'data' => $entry === null ? null : TimeEntryResource::make($entry)->resolve($request),
        ]);
    }

    public function start(StartTimerRequest $request): TimeEntryResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_OWN_TIME);

        $validated = $request->validated();

        return new TimeEntryResource($this->timer->start(
            $context->companyId,
            $context->userId,
            (int) $validated['task_id'],
            $validated['description'] ?? null,
            self::flag($request, 'billable'),
        ));
    }

    public function stop(StopTimerRequest $request): TimeEntryResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_OWN_TIME);

        return new TimeEntryResource($this->timer->stop(
            $context->companyId,
            $context->userId,
            $request->validated()['description'] ?? null,
            self::flag($request, 'billable'),
        ));
    }

    /** Start the caller's clock on one task, straight from its row or card. */
    public function startOnTask(StartTaskTimerRequest $request, int $id): TimeEntryResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TASK);
        $this->authorize($context, Abilities::VIEW_OWN_TIME);

        return new TimeEntryResource($this->timer->start(
            $context->companyId,
            $context->userId,
            $id,
            $request->validated()['description'] ?? null,
            self::flag($request, 'billable'),
        ));
    }

    /** Stop the caller's clock, but only while it is running on this task. */
    public function stopOnTask(StopTimerRequest $request, int $id): TimeEntryResource
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TASK);
        $this->authorize($context, Abilities::VIEW_OWN_TIME);

        return new TimeEntryResource($this->timer->stopOn(
            $context->companyId,
            $context->userId,
            $id,
            $request->validated()['description'] ?? null,
            self::flag($request, 'billable'),
        ));
    }

    /** Throw the running entry away without recording any time. */
    public function destroy(Request $request): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_OWN_TIME);

        $this->timer->discard($context->companyId, $context->userId);

        return response()->json(['success' => true]);
    }

    /**
     * A boolean the caller sent, or null when they said nothing about it.
     *
     * The service treats null as "leave it alone", so an omitted flag has to
     * stay distinguishable from a flag that was sent as false.
     */
    private static function flag(Request $request, string $key): ?bool
    {
        return $request->has($key) ? $request->boolean($key) : null;
    }
}
