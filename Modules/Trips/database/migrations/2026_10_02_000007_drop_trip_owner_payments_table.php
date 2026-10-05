<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop trip_owner_payments: the trip's own owner-payment ledger.
 *
 * The payable for a trip's lorry hire lives on the Lorry Receipt's bill
 * (host Bills + SupplierPayments); the trip only displays it. This table was
 * never written by any screen and held no rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('trip_owner_payments');
    }

    public function down(): void
    {
        if (Schema::hasTable('trip_owner_payments')) {
            return;
        }

        Schema::create('trip_owner_payments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('trip_id');
            $table->bigInteger('amount');
            $table->string('method', 32)->nullable();
            $table->string('note')->nullable();
            $table->date('payment_date');
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'trip_id']);
        });
    }
};
