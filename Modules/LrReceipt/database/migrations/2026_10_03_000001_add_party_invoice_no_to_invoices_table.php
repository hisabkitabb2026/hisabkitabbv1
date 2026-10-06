<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'tr_party_invoice_no')) {
                $table->text('tr_party_invoice_no')->nullable()->after('tr_charged_weight');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'tr_party_invoice_no')) {
                $table->dropColumn('tr_party_invoice_no');
            }
        });
    }
};
