<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $cols = [
                'tr_contract_no' => fn () => $table->text('tr_contract_no')->nullable(),
                'tr_lorry_no' => fn () => $table->text('tr_lorry_no')->nullable(),
                'tr_paid_to' => fn () => $table->text('tr_paid_to')->nullable(),
                'tr_lorry_hire_amount' => fn () => $table->text('tr_lorry_hire_amount')->nullable(),
                'tr_other_charges_amount' => fn () => $table->text('tr_other_charges_amount')->nullable(),
                'tr_advance_amount' => fn () => $table->text('tr_advance_amount')->nullable(),
                'tr_advance_cash_cheque_no' => fn () => $table->text('tr_advance_cash_cheque_no')->nullable(),
                'tr_advance_on' => fn () => $table->text('tr_advance_on')->nullable(),
                'tr_advance_bank' => fn () => $table->text('tr_advance_bank')->nullable(),
                'tr_received_no_bilties' => fn () => $table->text('tr_received_no_bilties')->nullable(),
                'tr_detention_amount' => fn () => $table->text('tr_detention_amount')->nullable(),
                'tr_extra_hire_amount' => fn () => $table->text('tr_extra_hire_amount')->nullable(),
                'tr_final_other_amount' => fn () => $table->text('tr_final_other_amount')->nullable(),
                'tr_final_balance_paid_at' => fn () => $table->text('tr_final_balance_paid_at')->nullable(),
                'tr_final_balance_on' => fn () => $table->text('tr_final_balance_on')->nullable(),
                'tr_net_amount_payable' => fn () => $table->text('tr_net_amount_payable')->nullable(),
                'tr_final_cash_cheque_no' => fn () => $table->text('tr_final_cash_cheque_no')->nullable(),
                'tr_final_bank' => fn () => $table->text('tr_final_bank')->nullable(),
                'tr_owner_name' => fn () => $table->text('tr_owner_name')->nullable(),
                'tr_owner_address' => fn () => $table->text('tr_owner_address')->nullable(),
                'tr_owner_phone' => fn () => $table->text('tr_owner_phone')->nullable(),
                'tr_owner_bank_account_no' => fn () => $table->text('tr_owner_bank_account_no')->nullable(),
                'tr_driver_name' => fn () => $table->text('tr_driver_name')->nullable(),
                'tr_driver_address' => fn () => $table->text('tr_driver_address')->nullable(),
                'tr_driver_licence_no' => fn () => $table->text('tr_driver_licence_no')->nullable(),
                'tr_broker_name' => fn () => $table->text('tr_broker_name')->nullable(),
                'tr_broker_address' => fn () => $table->text('tr_broker_address')->nullable(),
                'tr_broker_phone' => fn () => $table->text('tr_broker_phone')->nullable(),
            ];

            foreach ($cols as $col => $creator) {
                if (! Schema::hasColumn('invoices', $col)) {
                    $creator();
                }
            }

            if (! Schema::hasColumn('invoices', 'tr_owner_customer_id')) {
                $table->unsignedBigInteger('tr_owner_customer_id')->nullable()->index();
            }
            if (! Schema::hasColumn('invoices', 'tr_driver_customer_id')) {
                $table->unsignedBigInteger('tr_driver_customer_id')->nullable()->index();
            }
            if (! Schema::hasColumn('invoices', 'tr_broker_customer_id')) {
                $table->unsignedBigInteger('tr_broker_customer_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'tr_contract_no', 'tr_lorry_no', 'tr_paid_to', 'tr_lorry_hire_amount',
                'tr_other_charges_amount', 'tr_advance_amount', 'tr_advance_cash_cheque_no',
                'tr_advance_on', 'tr_advance_bank', 'tr_received_no_bilties',
                'tr_detention_amount', 'tr_extra_hire_amount', 'tr_final_other_amount',
                'tr_final_balance_paid_at', 'tr_final_balance_on', 'tr_net_amount_payable',
                'tr_final_cash_cheque_no', 'tr_final_bank',
                'tr_owner_name', 'tr_owner_address', 'tr_owner_phone', 'tr_owner_bank_account_no',
                'tr_driver_name', 'tr_driver_address', 'tr_driver_licence_no',
                'tr_broker_name', 'tr_broker_address', 'tr_broker_phone',
                'tr_owner_customer_id', 'tr_driver_customer_id', 'tr_broker_customer_id',
            ]);
        });
    }
};
