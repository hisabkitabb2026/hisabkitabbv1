<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link each party (owner/driver/broker) on a Lorry Receipt to the
     * LorryPartyProfile it was filled from, so the party popup can offer
     * Edit for the selected profile when the receipt is re-opened.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['tr_owner_profile_id', 'tr_driver_profile_id', 'tr_broker_profile_id'] as $column) {
                if (! Schema::hasColumn('invoices', $column)) {
                    $table->unsignedBigInteger($column)->nullable()->index();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['tr_owner_profile_id', 'tr_driver_profile_id', 'tr_broker_profile_id']);
        });
    }
};
