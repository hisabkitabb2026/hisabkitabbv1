<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * HisabKitab feature — TDS (Tax Deducted at Source) support on customer payments and invoice allocations.
     */
    public function up(): void
    {
        Schema::table('customer_payments', function (Blueprint $table): void {
            $table->unsignedBigInteger('tds_amount')->default(0)->after('amount');
            $table->unsignedBigInteger('base_tds_amount')->default(0)->after('base_amount');
        });

        Schema::table('customer_payment_allocations', function (Blueprint $table): void {
            $table->unsignedBigInteger('tds_amount')->default(0)->after('amount');
            $table->unsignedBigInteger('base_tds_amount')->default(0)->after('base_amount');
        });
    }

    public function down(): void
    {
        Schema::table('customer_payment_allocations', function (Blueprint $table): void {
            $table->dropColumn(['tds_amount', 'base_tds_amount']);
        });

        Schema::table('customer_payments', function (Blueprint $table): void {
            $table->dropColumn(['tds_amount', 'base_tds_amount']);
        });
    }
};
