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
                // Hire Particulars (missing fields)
                'tr_lorry_hire' => fn () => $table->bigInteger('tr_lorry_hire')->nullable(),
                'tr_add_other_charges' => fn () => $table->bigInteger('tr_add_other_charges')->nullable(),
                'tr_advance_paid_by' => fn () => $table->text('tr_advance_paid_by')->nullable(),
                'tr_bank' => fn () => $table->text('tr_bank')->nullable(),
                'tr_advance_paid_rs' => fn () => $table->bigInteger('tr_advance_paid_rs')->nullable(),
                'tr_gross_hire_rupees' => fn () => $table->text('tr_gross_hire_rupees')->nullable(),
                'tr_balance_rupees' => fn () => $table->text('tr_balance_rupees')->nullable(),
                'tr_balance_amount' => fn () => $table->text('tr_balance_amount')->nullable(),
                'tr_balance_rupees_only' => fn () => $table->text('tr_balance_rupees_only')->nullable(),
                'tr_hire_passed_by' => fn () => $table->text('tr_hire_passed_by')->nullable(),
                'tr_hire_certified_by' => fn () => $table->text('tr_hire_certified_by')->nullable(),
                'tr_hire_prepared_by' => fn () => $table->text('tr_hire_prepared_by')->nullable(),
                'tr_advance_received_by' => fn () => $table->text('tr_advance_received_by')->nullable(),
                'tr_loading_remarks' => fn () => $table->text('tr_loading_remarks')->nullable(),

                // Final Payment (missing fields)
                'tr_grand_total' => fn () => $table->text('tr_grand_total')->nullable(),
                'tr_total_less_amount' => fn () => $table->text('tr_total_less_amount')->nullable(),
                'tr_final_total_extra' => fn () => $table->text('tr_final_total_extra')->nullable(),
                'tr_final_cash_cheque_on' => fn () => $table->text('tr_final_cash_cheque_on')->nullable(),
                'tr_final_rupees_only' => fn () => $table->text('tr_final_rupees_only')->nullable(),
                'tr_final_passed_by' => fn () => $table->text('tr_final_passed_by')->nullable(),
                'tr_final_certified_by' => fn () => $table->text('tr_final_certified_by')->nullable(),
                'tr_final_prepared_by' => fn () => $table->text('tr_final_prepared_by')->nullable(),
                'tr_final_payment_received_by' => fn () => $table->text('tr_final_payment_received_by')->nullable(),
                'tr_cash_cheque_no' => fn () => $table->text('tr_cash_cheque_no')->nullable(),

                // Destination Broker
                'tr_dest_broker_name' => fn () => $table->text('tr_dest_broker_name')->nullable(),
                'tr_dest_broker_address' => fn () => $table->text('tr_dest_broker_address')->nullable(),

                // Financer
                'tr_financer_name' => fn () => $table->text('tr_financer_name')->nullable(),
                'tr_financer_address' => fn () => $table->text('tr_financer_address')->nullable(),

                // Driver (missing fields)
                'tr_driver_place' => fn () => $table->text('tr_driver_place')->nullable(),
                'tr_driver_licence_issued_by' => fn () => $table->text('tr_driver_licence_issued_by')->nullable(),
            ];

            foreach ($cols as $col => $creator) {
                if (! Schema::hasColumn('invoices', $col)) {
                    $creator();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $columns = [
                'tr_lorry_hire', 'tr_add_other_charges', 'tr_advance_paid_by', 'tr_bank',
                'tr_advance_paid_rs', 'tr_gross_hire_rupees', 'tr_balance_rupees',
                'tr_balance_amount', 'tr_balance_rupees_only', 'tr_hire_passed_by',
                'tr_hire_certified_by', 'tr_hire_prepared_by', 'tr_advance_received_by',
                'tr_loading_remarks',
                'tr_grand_total', 'tr_total_less_amount', 'tr_final_total_extra',
                'tr_final_cash_cheque_on', 'tr_final_rupees_only', 'tr_final_passed_by',
                'tr_final_certified_by', 'tr_final_prepared_by', 'tr_final_payment_received_by',
                'tr_cash_cheque_no',
                'tr_dest_broker_name', 'tr_dest_broker_address',
                'tr_financer_name', 'tr_financer_address',
                'tr_driver_place', 'tr_driver_licence_issued_by',
            ];

            $existing = collect($columns)->filter(fn ($col) => Schema::hasColumn('invoices', $col))->all();
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
