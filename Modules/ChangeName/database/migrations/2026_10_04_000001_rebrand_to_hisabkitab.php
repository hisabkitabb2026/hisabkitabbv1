<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rebrands user-visible "InvoiceShelf" strings to "HisabKitab" in the
 * database-backed settings.
 *
 * The page <title>, login page heading/description, copyright text and
 * customer portal title are all stored in the settings / company_settings
 * tables. This migration writes "HisabKitab" values so the host's
 * hardcoded "InvoiceShelf" fallbacks are never shown.
 *
 * Rolling back deletes the rows so the host defaults return.
 */
return new class extends Migration
{
    /**
     * The instance-wide settings this module owns.
     */
    private const SETTINGS = [
        'admin_page_title' => 'HisabKitab - Self Hosted Invoicing Platform',
        'login_page_heading' => 'HisabKitab',
        'login_page_description' => 'Self Hosted Invoicing Platform',
        'copyright_text' => 'HisabKitab',
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::SETTINGS as $option => $value) {
            DB::table('settings')->updateOrInsert(
                ['option' => $option],
                [
                    'value' => $value,
                    'updated_at' => $now,
                ],
            );
        }

        // Set the customer portal page title for every existing company.
        $companyIds = DB::table('companies')->pluck('id');

        foreach ($companyIds as $companyId) {
            DB::table('company_settings')->updateOrInsert(
                ['option' => 'customer_portal_page_title', 'company_id' => $companyId],
                [
                    'value' => 'HisabKitab',
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('option', array_keys(self::SETTINGS))->delete();

        DB::table('company_settings')
            ->where('option', 'customer_portal_page_title')
            ->delete();
    }
};
