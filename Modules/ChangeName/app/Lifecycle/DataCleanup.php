<?php

declare(strict_types=1);

namespace Modules\ChangeName\Lifecycle;

use Illuminate\Support\Facades\DB;
use InvoiceShelf\Modules\Contracts\DataCleanup;

/**
 * Removes the branding settings written by the module's migration.
 *
 * On uninstall, the settings rows are deleted so the host's hardcoded
 * defaults ("InvoiceShelf") return.
 */
class DataCleanup implements DataCleanup
{
    public function cleanup(): void
    {
        DB::table('settings')->whereIn('option', [
            'admin_page_title',
            'login_page_heading',
            'login_page_description',
            'copyright_text',
        ])->delete();

        DB::table('company_settings')
            ->where('option', 'customer_portal_page_title')
            ->delete();
    }
}
