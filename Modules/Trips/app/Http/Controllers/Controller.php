<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Modules\Trips\Support\Authorizes;
use Modules\Trips\Support\CompanyContext;

/**
 * What every controller in the module shares: the company and user of the
 * request, and the ability check.
 */
abstract class Controller extends BaseController
{
    public function __construct(protected readonly Authorizes $authorizes) {}

    protected function context(Request $request): CompanyContext
    {
        return CompanyContext::fromRequest($request);
    }

    protected function authorize(CompanyContext $context, string $ability): void
    {
        $this->authorizes->require($context, $ability);
    }
}
