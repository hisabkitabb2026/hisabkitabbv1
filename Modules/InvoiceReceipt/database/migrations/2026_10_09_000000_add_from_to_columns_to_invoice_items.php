<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// HisabKitab feature
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $cols = [
                'tr_from_name' => fn () => $table->text('tr_from_name')->nullable(),
                'tr_to_name' => fn () => $table->text('tr_to_name')->nullable(),
                'tr_truck_no' => fn () => $table->text('tr_truck_no')->nullable(),
                'tr_charged_weight' => fn () => $table->text('tr_charged_weight')->nullable(),
                'tr_other_charge' => fn () => $table->bigInteger('tr_other_charge')->nullable(),
            ];

            foreach ($cols as $col => $creator) {
                if (! Schema::hasColumn('invoice_items', $col)) {
                    $creator();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $columns = ['tr_from_name', 'tr_to_name', 'tr_truck_no', 'tr_charged_weight', 'tr_other_charge'];
            $existing = collect($columns)->filter(fn ($col) => Schema::hasColumn('invoice_items', $col))->all();
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
