<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // LR Receipt transport fields (tr_ prefix to avoid upstream collisions)
            if (! Schema::hasColumn('invoices', 'tr_from_code')) {
                $table->text('tr_from_code')->nullable()->after('template_name');
            }
            if (! Schema::hasColumn('invoices', 'tr_from_name')) {
                $table->text('tr_from_name')->nullable()->after('tr_from_code');
            }
            if (! Schema::hasColumn('invoices', 'tr_to_code')) {
                $table->text('tr_to_code')->nullable()->after('tr_from_name');
            }
            if (! Schema::hasColumn('invoices', 'tr_to_name')) {
                $table->text('tr_to_name')->nullable()->after('tr_to_code');
            }
            if (! Schema::hasColumn('invoices', 'tr_truck_no')) {
                $table->text('tr_truck_no')->nullable()->after('tr_to_name');
            }
            if (! Schema::hasColumn('invoices', 'tr_mode_of_payment')) {
                $table->text('tr_mode_of_payment')->nullable()->after('tr_truck_no');
            }
            if (! Schema::hasColumn('invoices', 'tr_gst_payable_by')) {
                $table->text('tr_gst_payable_by')->nullable()->after('tr_mode_of_payment');
            }
            if (! Schema::hasColumn('invoices', 'tr_description_goods')) {
                $table->text('tr_description_goods')->nullable()->after('tr_gst_payable_by');
            }
            if (! Schema::hasColumn('invoices', 'tr_hsn_code')) {
                $table->text('tr_hsn_code')->nullable()->after('tr_description_goods');
            }
            if (! Schema::hasColumn('invoices', 'tr_eway_bill_no')) {
                $table->text('tr_eway_bill_no')->nullable()->after('tr_hsn_code');
            }
            if (! Schema::hasColumn('invoices', 'tr_actual_weight')) {
                $table->text('tr_actual_weight')->nullable()->after('tr_eway_bill_no');
            }
            if (! Schema::hasColumn('invoices', 'tr_charged_weight')) {
                $table->text('tr_charged_weight')->nullable()->after('tr_actual_weight');
            }
            if (! Schema::hasColumn('invoices', 'tr_no_of_articles')) {
                $table->text('tr_no_of_articles')->nullable()->after('tr_charged_weight');
            }
            if (! Schema::hasColumn('invoices', 'tr_packing')) {
                $table->text('tr_packing')->nullable()->after('tr_no_of_articles');
            }
            if (! Schema::hasColumn('invoices', 'tr_basic_freight')) {
                $table->bigInteger('tr_basic_freight')->nullable()->after('tr_packing');
            }
            if (! Schema::hasColumn('invoices', 'tr_hamali')) {
                $table->bigInteger('tr_hamali')->nullable()->after('tr_basic_freight');
            }
            if (! Schema::hasColumn('invoices', 'tr_fov')) {
                $table->bigInteger('tr_fov')->nullable()->after('tr_hamali');
            }
            if (! Schema::hasColumn('invoices', 'tr_local_collection')) {
                $table->bigInteger('tr_local_collection')->nullable()->after('tr_fov');
            }
            if (! Schema::hasColumn('invoices', 'tr_door_delivery')) {
                $table->bigInteger('tr_door_delivery')->nullable()->after('tr_local_collection');
            }
            if (! Schema::hasColumn('invoices', 'tr_docket_charge')) {
                $table->bigInteger('tr_docket_charge')->nullable()->after('tr_door_delivery');
            }
            if (! Schema::hasColumn('invoices', 'tr_other_charge')) {
                $table->bigInteger('tr_other_charge')->nullable()->after('tr_docket_charge');
            }
            if (! Schema::hasColumn('invoices', 'tr_net_amount')) {
                $table->bigInteger('tr_net_amount')->nullable()->after('tr_other_charge');
            }
            if (! Schema::hasColumn('invoices', 'tr_consignee_customer_id')) {
                $table->unsignedBigInteger('tr_consignee_customer_id')->nullable()->index()->after('tr_net_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'tr_from_code', 'tr_from_name', 'tr_to_code', 'tr_to_name',
                'tr_truck_no', 'tr_mode_of_payment', 'tr_gst_payable_by',
                'tr_description_goods', 'tr_hsn_code', 'tr_eway_bill_no',
                'tr_actual_weight', 'tr_charged_weight', 'tr_no_of_articles',
                'tr_packing', 'tr_basic_freight', 'tr_hamali', 'tr_fov',
                'tr_local_collection', 'tr_door_delivery', 'tr_docket_charge',
                'tr_other_charge', 'tr_net_amount', 'tr_consignee_customer_id',
            ]);
        });
    }
};
