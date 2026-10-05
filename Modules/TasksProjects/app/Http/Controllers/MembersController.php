<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvoiceShelf\Modules\Contracts\Host\CompanyDataReader;
use Modules\TasksProjects\Support\Abilities;
use Modules\TasksProjects\Support\Authorizes;

/**
 * The company's members, for the assignee and project member pickers.
 *
 * This is host data, so it comes from the company data contract rather than
 * from a host model or a pivot table read by name.
 */
final class MembersController extends Controller
{
    public function __construct(Authorizes $authorizes, private readonly CompanyDataReader $companyData)
    {
        parent::__construct($authorizes);
    }

    public function __invoke(Request $request): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_PROJECT);

        return response()->json(['data' => $this->companyData->companyMembers($context->companyId)]);
    }
}
