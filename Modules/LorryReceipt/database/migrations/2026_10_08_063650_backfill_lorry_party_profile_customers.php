<?php

use App\Domains\Contacts\Application\CustomerService;
use App\Domains\Contacts\Models\Customer;
use App\Domains\Purchases\Application\PurchaseInputs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfill every existing LorryPartyProfile with a Customer record so it
     * shows in the Lorry Receipt customer dropdown. Profiles that already have
     * a customer_id are left alone.
     */
    public function up(): void
    {
        $profiles = \DB::table('tr_lorry_party_profiles')
            ->whereNull('customer_id')
            ->get();

        foreach ($profiles as $profile) {
            $companyId = (int) $profile->company_id;
            $currencyId = PurchaseInputs::companyCurrency($companyId);

            $customerId = Customer::create([
                'name' => $profile->name,
                'phone' => $profile->phone,
                'company_id' => $companyId,
                'currency_id' => $currencyId,
            ])->id;

            \DB::table('tr_lorry_party_profiles')
                ->where('id', $profile->id)
                ->update(['customer_id' => $customerId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive: we don't delete the Customer records that were
        // created, only unlink them from the profiles.
        \DB::table('tr_lorry_party_profiles')
            ->whereNotNull('customer_id')
            ->update(['customer_id' => null]);
    }
};

