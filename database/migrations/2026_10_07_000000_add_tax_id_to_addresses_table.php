<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * HisabKitab feature — GST number per address (billing / shipping).
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->string('tax_id', 50)->nullable()->after('fax');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->dropColumn('tax_id');
        });
    }
};
