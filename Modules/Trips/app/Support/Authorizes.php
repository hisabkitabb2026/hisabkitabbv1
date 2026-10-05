<?php

declare(strict_types=1);

namespace Modules\Trips\Support;

use Illuminate\Auth\Access\AuthorizationException;
use InvoiceShelf\Modules\Contracts\Host\ModuleAuthorization;
use InvoiceShelf\Modules\Registry;

/**
 * Ability checks against the host, for one company at a time.
 */
final class Authorizes
{
    public function __construct(private readonly ModuleAuthorization $authorization) {}

    public static function id(string $ability): string
    {
        return Registry::abilityId(Abilities::SLUG, $ability);
    }

    public function allows(CompanyContext $context, string $ability): bool
    {
        return $this->authorization->allows($context->userId, $context->companyId, self::id($ability));
    }

    /** @throws AuthorizationException when the user lacks the ability in this company */
    public function require(CompanyContext $context, string $ability): void
    {
        if (! $this->allows($context, $ability)) {
            throw new AuthorizationException('This action requires the '.self::id($ability).' ability.');
        }
    }
}
