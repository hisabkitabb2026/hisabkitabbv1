<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The documents linked to a trip. A shared lorry carries many LR receipts, so
 * this is a link table: one trip, many receipts. `type` is `lr` (revenue) or
 * `lorry` (cost). Amounts are denormalized integer minor units so the board
 * never re-reads the invoice tables.
 *
 * The unique key on (company_id, invoice_id) is the "unlinked" invariant: an
 * invoice can sit on one trip only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_receipts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('trip_id');
            $table->unsignedInteger('invoice_id');
            $table->string('type', 16);
            $table->bigInteger('amount')->default(0);
            $table->string('invoice_number')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('template_name')->nullable();
            $table->unsignedInteger('billed_invoice_id')->nullable();
            $table->dateTime('billed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'invoice_id']);
            $table->index(['company_id', 'trip_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_receipts');
    }
};
