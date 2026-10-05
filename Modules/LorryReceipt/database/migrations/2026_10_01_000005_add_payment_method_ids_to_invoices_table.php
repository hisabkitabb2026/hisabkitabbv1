<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'tr_advance_payment_method_id')) {
                $table->unsignedBigInteger('tr_advance_payment_method_id')->nullable();
            }
            if (! Schema::hasColumn('invoices', 'tr_final_payment_method_id')) {
                $table->unsignedBigInteger('tr_final_payment_method_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['tr_advance_payment_method_id', 'tr_final_payment_method_id']);
        });
    }
};
