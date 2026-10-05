<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the transport fields that existed in the old codebase but were missing
 * from the initial LorryReceipt module migration. All columns are prefixed
 * with tr_ and guarded by hasColumn checks for idempotency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $cols = [
                // Trip Details (missing from initial migration)
                'tr_no_of_pages' => fn () => $table->text('tr_no_of_pages')->nullable(),
                'tr_no_of_packages' => fn () => $table->text('tr_no_of_packages')->nullable(),
                'tr_actual_weight' => fn () => $table->text('tr_actual_weight')->nullable(),
                'tr_charged_weight' => fn () => $table->text('tr_charged_weight')->nullable(),

                // Vehicle Details (entire section was missing)
                'tr_regd_at' => fn () => $table->text('tr_regd_at')->nullable(),
                'tr_body_type' => fn () => $table->text('tr_body_type')->nullable(),
                'tr_make' => fn () => $table->text('tr_make')->nullable(),
                'tr_vehicle_model' => fn () => $table->text('tr_vehicle_model')->nullable(),
                'tr_colour' => fn () => $table->text('tr_colour')->nullable(),
                'tr_chasis_no' => fn () => $table->text('tr_chasis_no')->nullable(),
                'tr_engine_no' => fn () => $table->text('tr_engine_no')->nullable(),

                // Hire Particulars (missing fields)
                'tr_balance_payable_at' => fn () => $table->text('tr_balance_payable_at')->nullable(),
                'tr_loaded_by' => fn () => $table->text('tr_loaded_by')->nullable(),

                // Final Payment Details (missing fields)
                'tr_final_paid_to' => fn () => $table->text('tr_final_paid_to')->nullable(),
                'tr_less_advance_other_branch_amount' => fn () => $table->text('tr_less_advance_other_branch_amount')->nullable(),
                'tr_less_deduction_claims_amount' => fn () => $table->text('tr_less_deduction_claims_amount')->nullable(),

                // Owner Details (missing field)
                'tr_owner_pan_no' => fn () => $table->text('tr_owner_pan_no')->nullable(),

                // Driver Details (missing fields)
                'tr_driver_licence_date' => fn () => $table->text('tr_driver_licence_date')->nullable(),
                'tr_driver_rto_address' => fn () => $table->text('tr_driver_rto_address')->nullable(),
                'tr_driver_valid_up_to' => fn () => $table->text('tr_driver_valid_up_to')->nullable(),
                'tr_driver_bank_account_no' => fn () => $table->text('tr_driver_bank_account_no')->nullable(),

                // Broker Details (missing fields)
                'tr_broker_pan_no' => fn () => $table->text('tr_broker_pan_no')->nullable(),
                'tr_advice_date' => fn () => $table->text('tr_advice_date')->nullable(),
                'tr_broker_bank_account_no' => fn () => $table->text('tr_broker_bank_account_no')->nullable(),
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
                'tr_no_of_pages', 'tr_no_of_packages', 'tr_actual_weight', 'tr_charged_weight',
                'tr_regd_at', 'tr_body_type', 'tr_make', 'tr_vehicle_model', 'tr_colour',
                'tr_chasis_no', 'tr_engine_no',
                'tr_balance_payable_at', 'tr_loaded_by',
                'tr_final_paid_to', 'tr_less_advance_other_branch_amount', 'tr_less_deduction_claims_amount',
                'tr_owner_pan_no',
                'tr_driver_licence_date', 'tr_driver_rto_address', 'tr_driver_valid_up_to', 'tr_driver_bank_account_no',
                'tr_broker_pan_no', 'tr_advice_date', 'tr_broker_bank_account_no',
            ];

            // Only drop columns that exist (some may not have been added if migration was partial)
            $existing = collect($columns)->filter(fn ($col) => Schema::hasColumn('invoices', $col))->all();
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
