<?php

declare(strict_types=1);

namespace Modules\AccessRequest\Support;

use InvoiceShelf\Modules\Registry;

/**
 * Registers the module's scripts, styles with the host.
 */
final class ModuleRegistration
{
    public static function register(string $modulePath): void
    {
        Registry::registerScript('access-request', $modulePath.'/dist/init.js');
        Registry::registerStyle('access-request', $modulePath.'/dist/style.css');
    }
}
