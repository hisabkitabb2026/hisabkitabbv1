<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Modules\TasksProjects\Application\ReportService;
use Modules\TasksProjects\Http\Requests\ReportSummaryRequest;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Support\ModuleSettings;

/**
 * Read-only aggregates over logged time, for a dashboard or a reporting module.
 *
 * The range defaults to the current month. A caller who only has view-own-time
 * still gets a report: it covers their own time alone.
 */
final class ReportsController extends Controller
{
    public function __construct(
        Authorizes $authorizes,
        private readonly ReportService $reports,
        private readonly ModuleSettings $settings,
    ) {
        parent::__construct($authorizes);
    }

    public function summary(ReportSummaryRequest $request): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_OWN_TIME);

        $filters = $request->validated();
        $from = isset($filters['from']) ? Carbon::parse($filters['from']) : Carbon::now()->startOfMonth();
        $to = isset($filters['to']) ? Carbon::parse($filters['to']) : Carbon::now();

        return response()->json(['data' => $this->reports->summary(
            $context->companyId,
            $from->toDateString(),
            $to->toDateString(),
            $context->userId,
            $this->canSeeAllTime($context, $this->settings),
        )]);
    }
}
