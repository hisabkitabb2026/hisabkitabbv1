<?php

declare(strict_types=1);

namespace Modules\AccessRequest\Lifecycle;

use InvoiceShelf\Modules\Contracts\DataCleanup;

/**
 * Access Request module data cleanup.
 * This module stores no data — it only sends emails.
 */
class DataCleanup implements DataCleanup
{
    public function cleanup(): void
    {
        // No data to clean up — the module only sends transactional emails.
    }
}
