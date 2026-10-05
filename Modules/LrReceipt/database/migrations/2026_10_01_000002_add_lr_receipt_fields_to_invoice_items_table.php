<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_items', 'tr_consignment_number')) {
                $table->text('tr_consignment_number')->nullable();
            }
            if (! Schema::hasColumn('invoice_items', 'tr_pkg_weight')) {
                $table->text('tr_pkg_weight')->nullable();
            }
            if (! Schema::hasColumn('invoice_items', 'tr_party_inv_no')) {
                $table->text('tr_party_inv_no')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['tr_consignment_number', 'tr_pkg_weight', 'tr_party_inv_no']);
        });
    }
};
