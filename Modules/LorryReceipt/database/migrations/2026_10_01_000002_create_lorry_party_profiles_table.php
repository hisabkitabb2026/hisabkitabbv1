<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tr_lorry_party_profiles')) {
            Schema::create('tr_lorry_party_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('company_id')->index();
                $table->unsignedBigInteger('customer_id')->nullable()->index();
                $table->string('type');
                $table->string('code')->nullable();
                $table->string('name');
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('bank_account_no')->nullable();
                $table->string('licence_no')->nullable();
                $table->date('licence_date')->nullable();
                $table->string('licence_issued_by')->nullable();
                $table->text('rto_address')->nullable();
                $table->date('valid_up_to')->nullable();
                $table->string('place')->nullable();
                $table->string('destination_broker_name')->nullable();
                $table->text('destination_broker_address')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tr_lorry_party_profiles');
    }
};
