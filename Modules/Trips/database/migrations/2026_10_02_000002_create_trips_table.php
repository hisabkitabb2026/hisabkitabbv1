<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One trip is one lorry's journey: the card on the board. Revenue and cost
 * live on the linked receipts (trip_receipts) and expenses, not here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('trip_no');
            $table->unsignedBigInteger('status_id');
            $table->decimal('board_position', 20, 10)->default(0);
            $table->string('from_city')->nullable();
            $table->string('to_city')->nullable();
            $table->date('pickup_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->string('goods')->nullable();
            $table->string('weight')->nullable();
            $table->string('e_way_bill')->nullable();
            $table->string('lorry_no')->nullable();
            $table->unsignedBigInteger('owner_party_id')->nullable();
            $table->unsignedBigInteger('driver_party_id')->nullable();
            $table->unsignedBigInteger('broker_party_id')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'trip_no']);
            $table->index(['company_id', 'status_id', 'board_position']);
            $table->index(['company_id', 'owner_party_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
