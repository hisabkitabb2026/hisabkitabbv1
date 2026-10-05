<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds missing columns to tr_lorry_party_profiles that existed in the old
 * codebase (pan_number, advice_no, advice_date, alternate_phone, bank details, etc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tr_lorry_party_profiles', function (Blueprint $table) {
            $cols = [
                'alternate_phone' => fn () => $table->string('alternate_phone')->nullable(),
                'pan_number' => fn () => $table->string('pan_number')->nullable(),
                'gstin' => fn () => $table->string('gstin')->nullable(),
                'bank_name' => fn () => $table->string('bank_name')->nullable(),
                'bank_account_holder_name' => fn () => $table->string('bank_account_holder_name')->nullable(),
                'ifsc_code' => fn () => $table->string('ifsc_code')->nullable(),
                'upi_id' => fn () => $table->string('upi_id')->nullable(),
                'status' => fn () => $table->string('status')->default('active'),
                'notes' => fn () => $table->text('notes')->nullable(),
                'advice_no' => fn () => $table->string('advice_no')->nullable(),
                'advice_date' => fn () => $table->date('advice_date')->nullable(),
            ];

            foreach ($cols as $col => $creator) {
                if (! Schema::hasColumn('tr_lorry_party_profiles', $col)) {
                    $creator();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tr_lorry_party_profiles', function (Blueprint $table) {
            $columns = ['alternate_phone', 'pan_number', 'gstin', 'bank_name',
                'bank_account_holder_name', 'ifsc_code', 'upi_id', 'status',
                'notes', 'advice_no', 'advice_date'];

            $existing = collect($columns)->filter(fn ($col) => Schema::hasColumn('tr_lorry_party_profiles', $col))->all();
            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
