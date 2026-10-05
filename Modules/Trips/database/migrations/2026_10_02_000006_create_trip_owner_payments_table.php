<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Money paid to the lorry owner for a trip. The owner balance is the trip's
 * cost minus the sum of these rows; a trip settles when the customer has paid
 * and this balance is zero.
 */
return new class extends Migration
{
    public function up(): void
    {
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

    public function down(): void
    {
        Schema::dropIfExists('trip_owner_payments');
    }
};
