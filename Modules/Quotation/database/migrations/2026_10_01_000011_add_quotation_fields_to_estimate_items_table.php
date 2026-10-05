<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_items', function (Blueprint $table) {
            // Quotation line-item fields (tr_ prefix to avoid upstream collisions).
            //   tr_station_name — the station (replaces "Consignment No")
            //   tr_rate_*mt     — one rate column per vehicle capacity. The user
            //                     types a rate into each capacity field; the item
            //                     amount is the sum of all capacity rates.
            if (! Schema::hasColumn('estimate_items', 'tr_station_name')) {
                $table->text('tr_station_name')->nullable();
            }
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
            $cols = ['tr_station_name'];
            foreach (['9', '10', '12', '15', '18', '24', '30'] as $mt) {
                $cols[] = "tr_rate_{$mt}mt";
            }
            $table->dropColumn($cols);
        });
    }
};
