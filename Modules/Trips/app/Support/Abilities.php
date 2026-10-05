<?php

declare(strict_types=1);

namespace Modules\Trips\Support;

/**
 * Ability names the Trips module contributes to the host catalogue.
 *
 * The constants hold the bare, un-namespaced names: `Registry::registerAbility()`
 * namespaces every module ability as `{slug}:{ability}` at registration time.
 */
final class Abilities
{
    public const SLUG = 'trips';

    public const VIEW_TRIP = 'view-trip';

    public const CREATE_TRIP = 'create-trip';

    public const EDIT_TRIP = 'edit-trip';

    public const DELETE_TRIP = 'delete-trip';
}
