<?php

declare(strict_types=1);

namespace Modules\ChangeName\Support;

use InvoiceShelf\Modules\Registry;

/**
 * Registers the module's frontend script with the host.
 *
 * The script overrides user-visible "InvoiceShelf" i18n strings with
 * "HisabKitab" via the extension API's addMessages hook.
 */
final class ModuleRegistration
{
    public static function register(string $modulePath): void
    {
        Registry::registerScript('change-name', $modulePath.'/dist/init.js');
    }
}
