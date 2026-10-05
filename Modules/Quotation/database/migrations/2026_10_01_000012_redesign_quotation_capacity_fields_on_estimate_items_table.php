<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redesign: replace the single tr_capacity + tr_rate columns with one rate
 * column per vehicle capacity (9 mt … 30 mt). Each capacity is now its own
 * labeled input field where the user types a rate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            // Drop the old single-capacity columns from the first iteration.
            if (Schema::hasColumn('estimate_items', 'tr_capacity')) {
                $table->dropColumn('tr_capacity');
            }
            if (Schema::hasColumn('estimate_items', 'tr_rate')) {
                $table->dropColumn('tr_rate');
            }

            // Add one rate column per capacity.
            foreach (['9', '10', '12', '15', '18', '24', '30'] as $mt) {
                $col = "tr_rate_{$mt}mt";
                if (! Schema::hasColumn('estimate_items', $col)) {
                    $table->decimal($col, 15, 2)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            foreach (['9', '10', '12', '15', '18', '24', '30'] as $mt) {
                $col = "tr_rate_{$mt}mt";
                if (Schema::hasColumn('estimate_items', $col)) {
                    $table->dropColumn($col);
                }
            }
            // Restore the old columns (best-effort).
            if (! Schema::hasColumn('estimate_items', 'tr_capacity')) {
                $table->string('tr_capacity')->nullable();
            }
            if (! Schema::hasColumn('estimate_items', 'tr_rate')) {
                $table->decimal('tr_rate', 15, 2)->nullable();
            }
        });
    }
};
