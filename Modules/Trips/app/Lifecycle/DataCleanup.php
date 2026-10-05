<?php

declare(strict_types=1);

namespace Modules\Trips\Lifecycle;

use Illuminate\Support\Facades\Schema;
use InvoiceShelf\Modules\Contracts\DataCleanup as DataCleanupContract;

/**
 * Cleanup run when the module is uninstalled. The module owns no settings and
 * no files outside its tables, so dropping them is the whole job. Idempotent:
 * every drop is guarded.
 */
class DataCleanup implements DataCleanupContract
{
    public function cleanup(): void
    {
        foreach (['trip_owner_payments', 'trip_expenses', 'trip_events', 'trip_receipts', 'trips', 'trip_statuses'] as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }
    }
}
