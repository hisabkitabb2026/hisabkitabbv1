<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;
use Modules\TasksProjects\Support\CompanyContext;
use Modules\TasksProjects\Support\ModuleSettings;

/**
 * What every controller in the module shares: the company and user of the
 * request, the ability check, and the page it should return.
 *
 * Controllers stay thin. They authorize, validate through a form request, call
 * a service and hand the result to a resource; the rules themselves live in the
 * services, and company scoping lives in the services' findForCompany.
 */
abstract class Controller extends BaseController
{
    public const DEFAULT_LIMIT = 15;

    public const MAX_LIMIT = 100;

    public function __construct(protected readonly Authorizes $authorizes) {}

    protected function context(Request $request): CompanyContext
    {
        return CompanyContext::fromRequest($request);
    }

    protected function authorize(CompanyContext $context, string $ability): void
    {
        $this->authorizes->require($context, $ability);
    }

    protected function allows(CompanyContext $context, string $ability): bool
    {
        return $this->authorizes->allows($context, $ability);
    }

    /**
     * Whether the caller sees other members' time: either the ability, or the
     * company setting that opens the timesheet to everyone.
     */
    protected function canSeeAllTime(CompanyContext $context, ModuleSettings $settings): bool
    {
        return $this->allows($context, Abilities::VIEW_ALL_TIME)
            || $settings->membersSeeAllTime($context->companyId);
    }

    /** `?limit=`, defaulted and clamped rather than rejected. */
    protected function limit(Request $request): int
    {
        $limit = $request->integer('limit');

        return $limit <= 0 ? self::DEFAULT_LIMIT : min($limit, self::MAX_LIMIT);
    }

    /**
     * Page an already-loaded collection.
     *
     * The services own every query and hand back whole collections, so the page
     * is cut here rather than in a second query. The lists this module serves
     * are one company's projects, tasks and time entries, which is the scale
     * that fits in memory comfortably.
     *
     * @template TValue
     *
     * @param  Collection<int, TValue>  $items
     * @return LengthAwarePaginator<int, TValue>
     */
    protected function paginate(Collection $items, Request $request): LengthAwarePaginator
    {
        $perPage = $this->limit($request);
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ],
        );
    }
}
