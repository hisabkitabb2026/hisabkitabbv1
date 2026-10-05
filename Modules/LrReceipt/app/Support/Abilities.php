<?php

declare(strict_types=1);

namespace Modules\LrReceipt\Support;

/**
 * Ability names the LR Receipt module contributes to the host catalogue.
 *
 * The constants hold the bare, un-namespaced names: Registry::registerAbility()
 * namespaces every module ability as '{slug}:{ability}' at registration time.
 * Build the stored id with Registry::abilityId(Abilities::SLUG, ...) wherever
 * the namespaced form is needed.
 */
final class Abilities
{
    public const SLUG = 'lr-receipt';

    public const VIEW_LR_RECEIPT = 'view-lr-receipt';

    /** Host abilities the module's own abilities depend on. */
    public const HOST_VIEW_INVOICE = 'view-invoice';
}
