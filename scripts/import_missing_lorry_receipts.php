<?php

declare(strict_types=1);

/**
 * Script to import missing Lorry Receipts (Challan Nos) into Company 1.
 *
 * For each record:
 * 1. Creates/finds a LorryPartyProfile for the 'Paid To' entity.
 * 2. Ensures the corresponding Supplier and Customer are created and linked.
 * 3. Creates the Invoice (Lorry Receipt) with all transport particulars:
 *    - Challan No (invoice_number)
 *    - Paid To & Final Paid To (tr_paid_to, tr_final_paid_to, tr_owner_name)
 *    - Lorry Hire (tr_lorry_hire_amount, sub_total, total)
 *    - Advance Paid (tr_advance_amount)
 *    - Balance Due (tr_balance_amount, due_amount)
 *    - LR Number (tr_received_no_bilties)
 * 4. Syncs purchasing payables via LorryReceiptPayablesService:
 *    - Creates an open Bill under the 'Paid To' Supplier for the Lorry Hire amount.
 *    - Records an Advance Payment for the Advance amount allocated to that Bill.
 *    - Leaves the remaining balance as due amount.
 */

use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\User;
use App\Domains\Purchases\Application\PurchaseInputs;
use App\Domains\Sales\Application\SerialNumberService;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use App\Support\PublicToken;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Modules\LorryReceipt\Application\LorryReceiptPayablesService;
use Modules\LorryReceipt\Models\LorryPartyProfile;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$companyId = 1;

// Authenticate as company owner/user for any Bouncer or session checks
$company = Company::find($companyId);
$user = $company?->owner ?? $company?->users()->first() ?? User::query()->first();
if ($user) {
    auth()->login($user);
}

$payables = app(LorryReceiptPayablesService::class);
$currencyId = PurchaseInputs::companyCurrency($companyId);

$records = [
    ['challan_no' => '5',  'paid_to' => 'Nandu ROad Carrier',                'hire' => 46000, 'advance' => 42000, 'lr_no' => '106', 'date' => '2026-07-08'],
    ['challan_no' => '6',  'paid_to' => 'All india ROadlines',               'hire' => 44000, 'advance' => 41000, 'lr_no' => '107', 'date' => '2026-07-10'],
    ['challan_no' => '7',  'paid_to' => 'Shree SHyam Trans Container',       'hire' => 72000, 'advance' => 67000, 'lr_no' => '113', 'date' => '2026-07-12'],
    ['challan_no' => '8',  'paid_to' => 'All india ROadlines',               'hire' => 16500, 'advance' => 15500, 'lr_no' => '115', 'date' => '2026-07-14'],
    ['challan_no' => '9',  'paid_to' => 'EATA kanpur transport',             'hire' => 74000, 'advance' => 69000, 'lr_no' => '112', 'date' => '2026-07-16'],
    ['challan_no' => '10', 'paid_to' => 'INDIA KING ROADWAYS',               'hire' => 95000, 'advance' => 90000, 'lr_no' => '110 111', 'date' => '2026-07-18'],
    ['challan_no' => '11', 'paid_to' => 'SAKSHI CONTAINER SERVICE',         'hire' => 64000, 'advance' => 60000, 'lr_no' => '114', 'date' => '2026-07-20'],
    ['challan_no' => '12', 'paid_to' => 'ANDHRA CAPITAL',                   'hire' => 38000, 'advance' => 35000, 'lr_no' => '116', 'date' => '2026-07-22'],
    ['challan_no' => '13', 'paid_to' => 'BABA RAMDEV FREIGHT CARRIER',       'hire' => 62000, 'advance' => 57000, 'lr_no' => '117', 'date' => '2026-07-25'],
    ['challan_no' => '14', 'paid_to' => 'EATA kanpur transport',             'hire' => 107000, 'advance' => 102000, 'lr_no' => '118', 'date' => '2026-07-27'],
    ['challan_no' => '16', 'paid_to' => 'POONAM ROADLINES',                  'hire' => 11000, 'advance' => 10000, 'lr_no' => '120', 'date' => '2026-08-01'],
    ['challan_no' => '17', 'paid_to' => 'RAVI TRANSPORT',                   'hire' => 85000, 'advance' => 80000, 'lr_no' => '121', 'date' => '2026-08-03'],
    ['challan_no' => '18', 'paid_to' => 'JEEN MATA TRANPSORT',               'hire' => 54000, 'advance' => 50000, 'lr_no' => '122 123', 'date' => '2026-08-05'],
    ['challan_no' => '19', 'paid_to' => 'JEEN MATA TRANPSORT',               'hire' => 71000, 'advance' => 66000, 'lr_no' => '124', 'date' => '2026-08-07'],
    ['challan_no' => '20', 'paid_to' => 'SHREE NATH RAODLINES',              'hire' => 14400, 'advance' => 13400, 'lr_no' => '125', 'date' => '2026-08-09'],
    ['challan_no' => '21', 'paid_to' => 'JAI HANUMAN TRANSPORT COROPORATION', 'hire' => 65000, 'advance' => 60000, 'lr_no' => '108', 'date' => '2026-08-11'],
    ['challan_no' => '22', 'paid_to' => 'JAI HANUMAN TRANSPORT COROPORATION', 'hire' => 65000, 'advance' => 60000, 'lr_no' => '109', 'date' => '2026-08-13'],
    ['challan_no' => '23', 'paid_to' => 'NEW KISHAN ROADLINES',              'hire' => 9000,  'advance' => 8000,  'lr_no' => '101', 'date' => '2026-08-15'],
    ['challan_no' => '35', 'paid_to' => 'NEW SIDDIQUE TRANSPORT',            'hire' => 25000, 'advance' => 24000, 'lr_no' => '152', 'date' => '2026-08-20'],
    ['challan_no' => '39', 'paid_to' => 'EATA kanpur transport',             'hire' => 133000, 'advance' => 125000, 'lr_no' => '141 142', 'date' => '2026-08-22'],
    ['challan_no' => '40', 'paid_to' => 'CHITRANSH CARGO',                   'hire' => 54000, 'advance' => 50000, 'lr_no' => '143 144', 'date' => '2026-08-23'],
    ['challan_no' => '41', 'paid_to' => 'CHAITNYA CARGO MOVERS',             'hire' => 64000, 'advance' => 60000, 'lr_no' => '145', 'date' => '2026-08-24'],
    ['challan_no' => '42', 'paid_to' => 'KIRUBA ROADWAYS',                   'hire' => 102000, 'advance' => 97000, 'lr_no' => '153 154 202 203', 'date' => '2026-08-25'],
    ['challan_no' => '44', 'paid_to' => 'VIJAY ROADLINES',                   'hire' => 47500, 'advance' => 44500, 'lr_no' => '149', 'date' => '2026-08-26'],
    ['challan_no' => '45', 'paid_to' => 'SHREE GNESH ROALINES',              'hire' => 27000, 'advance' => 26000, 'lr_no' => '150', 'date' => '2026-08-27'],
    ['challan_no' => '46', 'paid_to' => 'POONAM ROADLINES',                  'hire' => 7000,  'advance' => 6500,  'lr_no' => '301', 'date' => '2026-08-28'],
];

echo 'Starting import of '.count($records)." missing Lorry Receipts for Company {$companyId}...\n\n";

$createdCount = 0;
$skippedCount = 0;

foreach ($records as $index => $row) {
    $challanNo = (string) $row['challan_no'];
    $paddedChallan = str_pad($challanNo, 3, '0', STR_PAD_LEFT);
    $paidTo = trim($row['paid_to']);
    $hire = (int) $row['hire'];
    $advance = (int) $row['advance'];
    $lrNo = trim((string) $row['lr_no']);
    $invoiceDate = $row['date'];

    // Check if invoice already exists
    $existing = Invoice::query()
        ->where('company_id', $companyId)
        ->where('template_name', 'lorry_receipt')
        ->where(function ($q) use ($challanNo, $paddedChallan) {
            $q->where('invoice_number', $challanNo)
                ->orWhere('invoice_number', $paddedChallan);
        })
        ->first();

    if ($existing !== null) {
        echo "[-] Challan {$challanNo} already exists (ID: {$existing->id}). Skipping.\n";
        $skippedCount++;

        continue;
    }

    DB::beginTransaction();
    try {
        // 1. Ensure Lorry Party Profile exists
        $profile = LorryPartyProfile::query()
            ->where('company_id', $companyId)
            ->where('name', $paidTo)
            ->first();

        if ($profile === null) {
            $profile = LorryPartyProfile::create([
                'company_id' => $companyId,
                'name' => $paidTo,
                'type' => 'OWNER',
            ]);
        }

        // 2. Ensure linked Supplier and Customer
        $supplier = $payables->ensureSupplier($profile);
        $customer = $payables->ensureCustomer($profile);

        // 3. Financial amounts (in paise / minor units)
        $hirePaise = $hire * 100;
        $advPaise = $advance * 100;
        $duePaise = max(0, $hirePaise - $advPaise);
        $balanceRs = (string) max(0, $hire - $advance);

        // 4. Create Invoice
        $invoice = Invoice::create([
            'company_id' => $companyId,
            'customer_id' => $customer->id,
            'creator_id' => $user?->id ?? 1,
            'currency_id' => $currencyId,
            'template_name' => 'lorry_receipt',
            'invoice_number' => $challanNo,
            'invoice_date' => $invoiceDate.' 00:00:00',
            'due_date' => Carbon::parse($invoiceDate)->addDays(30)->toDateString(),
            'status' => Invoice::STATUS_SENT,
            'paid_status' => $advPaise > 0 ? Invoice::STATUS_PARTIALLY_PAID : Invoice::STATUS_UNPAID,
            'sub_total' => $hirePaise,
            'total' => $hirePaise,
            'due_amount' => $duePaise,
            'base_sub_total' => $hirePaise,
            'base_total' => $hirePaise,
            'base_due_amount' => $duePaise,
            'tax_per_item' => 'NO_TAX',
            'discount_per_item' => 'NO_DISCOUNT',
            'discount_type' => 'fixed',
            'discount' => 0,
            'discount_val' => 0,
            'tax' => 0,
            'base_tax' => 0,
            'sent' => true,
            'viewed' => false,
            'exchange_rate' => 1,
            'unique_hash' => PublicToken::make(),
            'tr_no_of_pages' => '1',
            'tr_paid_to' => $paidTo,
            'tr_final_paid_to' => $paidTo,
            'tr_owner_name' => $paidTo,
            'tr_owner_profile_id' => $profile->id,
            'tr_owner_customer_id' => $customer->id,
            'tr_lorry_hire_amount' => (string) $hire,
            'tr_advance_amount' => (string) $advance,
            'tr_balance_amount' => $balanceRs,
            'tr_received_no_bilties' => $lrNo,
        ]);

        // 5. Line Item (Lorry Hire)
        InvoiceItem::create([
            'company_id' => $companyId,
            'invoice_id' => $invoice->id,
            'name' => 'Lorry Hire',
            'description' => 'Lorry Hire Charges',
            'price' => $hirePaise,
            'quantity' => 1,
            'discount_type' => 'fixed',
            'discount' => 0,
            'discount_val' => 0,
            'total' => $hirePaise,
            'tax' => 0,
        ]);

        // 6. Sequence Number
        try {
            $serial = (new SerialNumberService)
                ->setCompany($companyId)
                ->setCustomer($customer->id)
                ->setSequenceScope(['template_name' => 'lorry_receipt'])
                ->setModel($invoice)
                ->setNextNumbers();

            $invoice->forceFill([
                'sequence_number' => $serial->nextSequenceNumber ?: (int) $challanNo,
                'customer_sequence_number' => $serial->nextCustomerSequenceNumber ?: (int) $challanNo,
            ])->saveQuietly();
        } catch (Throwable $e) {
            $invoice->forceFill([
                'sequence_number' => (int) $challanNo,
                'customer_sequence_number' => (int) $challanNo,
            ])->saveQuietly();
        }

        // 7. Sync Payables (Supplier Bill + Advance Payment allocation)
        $payables->syncForInvoice($invoice);

        DB::commit();

        $invoice->refresh();
        echo "[+] [{$challanNo}] Saved Invoice ID: {$invoice->id} | Paid To: {$paidTo} | Hire: Rs.{$hire} | Adv: Rs.{$advance} | LR: {$lrNo} | Bill ID: ".($invoice->tr_bill_id ?? 'N/A').' | Adv Payment ID: '.($invoice->tr_advance_payment_id ?? 'N/A')."\n";
        $createdCount++;
    } catch (Throwable $e) {
        DB::rollBack();
        echo "[!] Error importing Challan {$challanNo}: ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
    }
}

echo "\nFinished. Created: {$createdCount}, Skipped: {$skippedCount}\n";
