<?php

declare(strict_types=1);

namespace Modules\TasksProjects\Support;

use Illuminate\Http\Request;

/**
 * The company and the user a request acts for.
 *
 * The company never comes from a request parameter: it is the `company` header
 * the host middleware has already validated for this user, which is what every
 * query in the module scopes on. The user is the authenticated caller, taken
 * through the framework contract so no host user model is ever imported.
 */
final readonly class CompanyContext
{
    public function __construct(public int $companyId, public int $userId) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            (int) $request->header('company'),
            (int) $request->user()->getAuthIdentifier(),
        );
    }
}
