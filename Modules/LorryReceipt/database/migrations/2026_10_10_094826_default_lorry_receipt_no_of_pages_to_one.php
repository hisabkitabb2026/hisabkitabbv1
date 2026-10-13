<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Trip Details "No Of Pages" input is gone from the form, so every lorry
 * receipt carries a page count of 1. Existing receipts without one are set to
 * 1 here; new ones are defaulted by the InvoiceObserver. The query builder
 * skips the model events, so no payables are re-synced by this backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('invoices')
            ->where('template_name', 'lorry_receipt')
            ->where(function ($query) {
                $query->whereNull('tr_no_of_pages')->orWhere('tr_no_of_pages', '');
            })
            ->update(['tr_no_of_pages' => '1']);
    }

    public function down(): void
    {
        // The previous page counts were not recorded, so there is nothing to restore.
    }
};
