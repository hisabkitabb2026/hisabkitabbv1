<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            // Quotation document-level fields (tr_ prefix to avoid upstream collisions)
            if (! Schema::hasColumn('estimates', 'tr_quotation_subject')) {
                $table->text('tr_quotation_subject')->nullable()->after('template_name');
            }
            if (! Schema::hasColumn('estimates', 'tr_validity_days')) {
                $table->unsignedInteger('tr_validity_days')->nullable()->after('tr_quotation_subject');
            }
            if (! Schema::hasColumn('estimates', 'tr_payment_terms')) {
                $table->text('tr_payment_terms')->nullable()->after('tr_validity_days');
            }
            if (! Schema::hasColumn('estimates', 'tr_delivery_terms')) {
                $table->text('tr_delivery_terms')->nullable()->after('tr_payment_terms');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn([
                'tr_quotation_subject',
                'tr_validity_days',
                'tr_payment_terms',
                'tr_delivery_terms',
            ]);
        });
    }
};
