<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// HisabKitab feature
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $cols = [
                'tr_time' => fn () => $table->text('tr_time')->nullable(),
                'tr_consignor' => fn () => $table->text('tr_consignor')->nullable(),
                'tr_consignor_phone' => fn () => $table->text('tr_consignor_phone')->nullable(),
                'tr_consignor_gst' => fn () => $table->text('tr_consignor_gst')->nullable(),
                'tr_consignee' => fn () => $table->text('tr_consignee')->nullable(),
                'tr_consignee_phone' => fn () => $table->text('tr_consignee_phone')->nullable(),
                'tr_consignee_gst' => fn () => $table->text('tr_consignee_gst')->nullable(),
                'tr_gst_through' => fn () => $table->text('tr_gst_through')->nullable(),
                'tr_delivery_at' => fn () => $table->text('tr_delivery_at')->nullable(),
                'tr_goods_value' => fn () => $table->text('tr_goods_value')->nullable(),
                'tr_pod_required' => fn () => $table->text('tr_pod_required')->nullable(),
            ];

            foreach ($cols as $col => $creator) {
                if (! Schema::hasColumn('invoices', $col)) {
                    $creator();
                }
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $cols = [
                'tr_consignment_date' => fn () => $table->text('tr_consignment_date')->nullable(),
                'tr_rate' => fn () => $table->text('tr_rate')->nullable(),
                'tr_lr_charge' => fn () => $table->bigInteger('tr_lr_charge')->nullable(),
                'tr_dd_charge' => fn () => $table->bigInteger('tr_dd_charge')->nullable(),
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
        Schema::table('invoices', function (Blueprint $table) {
            $columns = ['tr_time', 'tr_consignor', 'tr_consignor_phone', 'tr_consignor_gst',
                'tr_consignee', 'tr_consignee_phone', 'tr_consignee_gst', 'tr_gst_through',
                'tr_delivery_at', 'tr_goods_value', 'tr_pod_required'];
            $existing = collect($columns)->filter(fn ($col) => Schema::hasColumn('invoices', $col))->all();
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $columns = ['tr_consignment_date', 'tr_rate', 'tr_lr_charge', 'tr_dd_charge'];
            $existing = collect($columns)->filter(fn ($col) => Schema::hasColumn('invoice_items', $col))->all();
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
