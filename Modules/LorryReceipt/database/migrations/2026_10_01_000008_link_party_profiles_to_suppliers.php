<?php

use App\Domains\Sales\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\LorryReceipt\Application\LorryReceiptPayablesService;
use Modules\LorryReceipt\Models\LorryPartyProfile;

return new class extends Migration
{
    /**
     * Link every lorry party to a Supplier, and every lorry receipt to the
     * Bill and SupplierPayments that mirror its hire, so payables include
     * them from the first day.
     */
    public function up(): void
    {
        Schema::table('tr_lorry_party_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('tr_lorry_party_profiles', 'supplier_id')) {
                $table->unsignedBigInteger('supplier_id')->nullable()->index();
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            foreach (['tr_bill_id', 'tr_advance_payment_id', 'tr_final_payment_id'] as $column) {
                if (! Schema::hasColumn('invoices', $column)) {
                    $table->unsignedBigInteger($column)->nullable()->index();
                }
            }
        });

        $payables = app(LorryReceiptPayablesService::class);

        LorryPartyProfile::query()
            ->whereNull('supplier_id')
            ->orderBy('id')
            ->chunkById(100, function ($profiles) use ($payables) {
                foreach ($profiles as $profile) {
                    try {
                        $payables->ensureSupplier($profile);
                    } catch (Throwable $e) {
                        Log::warning('lorry-receipt: supplier link failed for profile '.$profile->id.': '.$e->getMessage());
                    }
                }
            });

        Invoice::query()
            ->where('template_name', 'lorry_receipt')
            ->whereNull('tr_bill_id')
            ->orderBy('id')
            ->chunkById(100, function ($invoices) use ($payables) {
                foreach ($invoices as $invoice) {
                    try {
                        $payables->syncForInvoice($invoice);
                    } catch (Throwable $e) {
                        Log::warning('lorry-receipt: payables backfill failed for invoice '.$invoice->id.': '.$e->getMessage());
                    }
                }
            });
    }

    public function down(): void
    {
        // The suppliers, bills and payments created by the backfill are real
        // financial records now; they stay. Only the link columns go.
        Schema::table('tr_lorry_party_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('tr_lorry_party_profiles', 'supplier_id')) {
                $table->dropColumn('supplier_id');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            $columns = ['tr_bill_id', 'tr_advance_payment_id', 'tr_final_payment_id'];
            $existing = collect($columns)->filter(fn ($col) => Schema::hasColumn('invoices', $col))->all();

            if (! empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
