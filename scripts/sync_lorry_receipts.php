<?php

declare(strict_types=1);

/**
 * Script to sync Lorry Receipts (Challan Nos) from OLD_DATA into Company 1.
 *
 * For each record in OLD_DATA:
 * 1. Checks if the Lorry Receipt already exists (by invoice_number matching challan_no).
 * 2. Fetches 'From' and 'To' from Invoice Receipts:
 *    - For the LR number(s) on the Challan, searches invoice_items in 'invoice_receipt' invoices.
 *    - If multiple matching records/items exist, selects the one where the Amount is MAXIMUM.
 *    - Falls back to 'lr_receipt' if no 'invoice_receipt' found.
 * 3. If the Lorry Receipt already exists:
 *    - Updates tr_lorry_no and tr_truck_no with Lorry Number.
 *    - Updates tr_from_name and tr_to_name with resolved From and To.
 *    - Updates tr_received_no_bilties with LR NO.
 * 4. If the Lorry Receipt does NOT exist:
 *    - Creates LorryPartyProfile, Supplier, Customer.
 *    - Creates the Invoice (lorry_receipt) with Hire, Advance, Balance, Lorry No, From, To, LR No.
 *    - Creates the line item.
 *    - Syncs Payables (Supplier Bill & Advance Payment allocation).
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

$company = Company::find($companyId);
$user = $company?->owner ?? $company?->users()->first() ?? User::query()->first();
if ($user) {
    auth()->login($user);
}

$payables = app(LorryReceiptPayablesService::class);
$currencyId = PurchaseInputs::companyCurrency($companyId);

$records = [
    ['challan_no' => '5',  'paid_to' => 'Nandu ROad Carrier',                'hire' => 46000,  'advance' => 42000,  'lr_no' => '106',                'lorry_no' => 'HR69D9329'],
    ['challan_no' => '6',  'paid_to' => 'All india ROadlines',               'hire' => 44000,  'advance' => 41000,  'lr_no' => '107',                'lorry_no' => 'MH12WJ3637'],
    ['challan_no' => '7',  'paid_to' => 'Shree SHyam Trans Container',       'hire' => 72000,  'advance' => 67000,  'lr_no' => '113',                'lorry_no' => 'MH23AU7157'],
    ['challan_no' => '8',  'paid_to' => 'All india ROadlines',               'hire' => 16500,  'advance' => 15500,  'lr_no' => '115',                'lorry_no' => 'MH48DC2512'],
    ['challan_no' => '9',  'paid_to' => 'EATA kanpur transport',             'hire' => 74000,  'advance' => 69000,  'lr_no' => '112',                'lorry_no' => 'UP75M9952'],
    ['challan_no' => '10', 'paid_to' => 'INDIA KING ROADWAYS',               'hire' => 95000,  'advance' => 90000,  'lr_no' => '110 111',            'lorry_no' => 'UP80ET6823'],
    ['challan_no' => '11', 'paid_to' => 'SAKSHI CONTAINER SERVICE',         'hire' => 64000,  'advance' => 60000,  'lr_no' => '114',                'lorry_no' => 'KA52B2062'],
    ['challan_no' => '12', 'paid_to' => 'ANDHRA CAPITAL',                   'hire' => 38000,  'advance' => 35000,  'lr_no' => '116',                'lorry_no' => 'DD01Q9756'],
    ['challan_no' => '13', 'paid_to' => 'BABA RAMDEV FREIGHT CARRIER',       'hire' => 62000,  'advance' => 57000,  'lr_no' => '117',                'lorry_no' => 'DD01AE9540'],
    ['challan_no' => '14', 'paid_to' => 'EATA kanpur transport',             'hire' => 107000, 'advance' => 102000, 'lr_no' => '118',                'lorry_no' => 'UP75M5517'],
    ['challan_no' => '16', 'paid_to' => 'POONAM ROADLINES',                  'hire' => 11000,  'advance' => 10000,  'lr_no' => '120',                'lorry_no' => 'MH04JK0253'],
    ['challan_no' => '17', 'paid_to' => 'RAVI TRANSPORT',                   'hire' => 85000,  'advance' => 80000,  'lr_no' => '121',                'lorry_no' => 'KA44A2339'],
    ['challan_no' => '18', 'paid_to' => 'JEEN MATA TRANPSORT',               'hire' => 54000,  'advance' => 50000,  'lr_no' => '122 123',            'lorry_no' => 'DD01N9770'],
    ['challan_no' => '19', 'paid_to' => 'JEEN MATA TRANPSORT',               'hire' => 71000,  'advance' => 66000,  'lr_no' => '124',                'lorry_no' => 'MH44U4815'],
    ['challan_no' => '20', 'paid_to' => 'SHREE NATH RAODLINES',              'hire' => 14400,  'advance' => 13400,  'lr_no' => '125',                'lorry_no' => 'MH04DS1424'],
    ['challan_no' => '21', 'paid_to' => 'JAI HANUMAN TRANSPORT COROPORATION', 'hire' => 65000,  'advance' => 60000,  'lr_no' => '108',                'lorry_no' => 'HR55BB4477'],
    ['challan_no' => '22', 'paid_to' => 'JAI HANUMAN TRANSPORT COROPORATION', 'hire' => 65000,  'advance' => 60000,  'lr_no' => '109',                'lorry_no' => 'HR55BC0377'],
    ['challan_no' => '23', 'paid_to' => 'NEW KISHAN ROADLINES',              'hire' => 9000,   'advance' => 8000,   'lr_no' => '101',                'lorry_no' => 'GJ15AT9874'],
    ['challan_no' => '35', 'paid_to' => 'NEW SIDDIQUE TRANSPORT',            'hire' => 25000,  'advance' => 24000,  'lr_no' => '152',                'lorry_no' => 'GJ15AX0474'],
    ['challan_no' => '39', 'paid_to' => 'EATA kanpur transport',             'hire' => 133000, 'advance' => 125000, 'lr_no' => '141 142',            'lorry_no' => 'UP82T7653'],
    ['challan_no' => '40', 'paid_to' => 'CHITRANSH CARGO',                   'hire' => 54000,  'advance' => 50000,  'lr_no' => '143 144',            'lorry_no' => 'MH48DQ4436'],
    ['challan_no' => '41', 'paid_to' => 'CHAITNYA CARGO MOVERS',             'hire' => 64000,  'advance' => 60000,  'lr_no' => '145',                'lorry_no' => 'DN09V9933'],
    ['challan_no' => '42', 'paid_to' => 'KIRUBA ROADWAYS',                   'hire' => 102000, 'advance' => 97000,  'lr_no' => '153 154 202 203',    'lorry_no' => 'TN88U2965'],
    ['challan_no' => '44', 'paid_to' => 'VIJAY ROADLINES',                   'hire' => 47500,  'advance' => 44500,  'lr_no' => '149',                'lorry_no' => 'KA04AF1959'],
    ['challan_no' => '45', 'paid_to' => 'SHREE GNESH ROALINES',              'hire' => 27000,  'advance' => 26000,  'lr_no' => '150',                'lorry_no' => 'GJ03CU3382'],
    ['challan_no' => '46', 'paid_to' => 'POONAM ROADLINES',                  'hire' => 7000,   'advance' => 6500,   'lr_no' => '301',                'lorry_no' => 'GJ15AU4976'],
    ['challan_no' => '75', 'paid_to' => 'RUPINDER KAUR GILL',                'hire' => 150000, 'advance' => 140000, 'lr_no' => '335',                'lorry_no' => 'CG04QT9695'],
    ['challan_no' => '76', 'paid_to' => 'CHOUDHARY TRANSPORT CO',            'hire' => 35000,  'advance' => 33000,  'lr_no' => '337',                'lorry_no' => 'RJ46GA5813'],
    ['challan_no' => '77', 'paid_to' => 'INDIAN ROAD CARRIER',               'hire' => 30000,  'advance' => 28000,  'lr_no' => '336',                'lorry_no' => 'WB23C7936'],
    ['challan_no' => '78', 'paid_to' => 'BERWAL CONTAINER SERVICE',          'hire' => 47500,  'advance' => 45500,  'lr_no' => '338',                'lorry_no' => 'DD01W9455'],
    ['challan_no' => '79', 'paid_to' => 'BABA RAMDEV FREIGHT CARRIER',       'hire' => 68000,  'advance' => 64000,  'lr_no' => '339',                'lorry_no' => 'NL01AB2872'],
    ['challan_no' => '80', 'paid_to' => 'SOUTH GOLDEN CARRIER',              'hire' => 28000,  'advance' => 27000,  'lr_no' => '340',                'lorry_no' => 'KL05BF5628'],
    ['challan_no' => '81', 'paid_to' => 'MAA SAMALESHWARI ROADLINES',        'hire' => 100000, 'advance' => 95000,  'lr_no' => '341',                'lorry_no' => 'MH48DC6209'],
    ['challan_no' => '82', 'paid_to' => 'NEW SIDDIQUE ROADLINES',            'hire' => 45000,  'advance' => 43000,  'lr_no' => '342 343',            'lorry_no' => 'MH24AB9592'],
];

echo "========================================================================\n";
echo "   SYNC LORRY RECEIPTS: OLD_DATA -> COMPANY 1\n";
echo "========================================================================\n\n";

/**
 * Helper to fetch From and To details for a set of LR numbers.
 * Priority: Invoice Receipt item where amount is MAX.
 * Fallback: LR Receipt.
 */
function resolveFromToForLrNumbers(string $lrNoString, int $companyId): array
{
    $lrTokens = preg_split('/\s+/', trim($lrNoString), -1, PREG_SPLIT_NO_EMPTY);
    if (empty($lrTokens)) {
        return ['from' => null, 'to' => null, 'source' => null, 'max_amount' => 0, 'invoice_date' => null];
    }

    $bestMatch = null;
    $maxAmount = -1;

    foreach ($lrTokens as $lr) {
        $cleanLr = trim($lr);
        if ($cleanLr === '') {
            continue;
        }

        $variants = array_values(array_unique(array_filter([
            $cleanLr,
            ltrim($cleanLr, '0'),
            str_pad($cleanLr, 3, '0', STR_PAD_LEFT),
        ])));

        // 1. Search in Invoice Receipt items
        $items = InvoiceItem::query()
            ->whereHas('invoice', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->where('template_name', 'invoice_receipt');
            })
            ->where(function ($q) use ($variants) {
                $q->whereIn('tr_consignment_number', $variants)
                    ->orWhereIn('name', $variants);
            })
            ->with('invoice')
            ->get();

        foreach ($items as $item) {
            $inv = $item->invoice;
            $amt = (float) ($item->total ?: ($item->price ?: ($inv->total ?? 0)));
            $from = trim((string) ($item->tr_from_name ?: ($inv->tr_from_name ?? '')));
            $to = trim((string) ($item->tr_to_name ?: ($inv->tr_to_name ?? '')));
            $invDate = $inv->invoice_date ? Carbon::parse($inv->invoice_date)->toDateString() : null;

            // Prefer candidates with From/To specified, and pick the one with MAX amount
            if (($from !== '' || $to !== '') && $amt > $maxAmount) {
                $maxAmount = $amt;
                $bestMatch = [
                    'from' => $from ?: null,
                    'to' => $to ?: null,
                    'source' => "Invoice Receipt #{$inv->invoice_number} (Item ID: {$item->id}, Amount: ".($amt / 100).')',
                    'max_amount' => $amt,
                    'invoice_date' => $invDate,
                ];
            }
        }

        // 2. Fallback to LR Receipt if no invoice_receipt item match so far
        if ($bestMatch === null) {
            $lrReceipt = Invoice::query()
                ->where('company_id', $companyId)
                ->where('template_name', 'lr_receipt')
                ->whereIn('invoice_number', $variants)
                ->first();

            if ($lrReceipt) {
                $amt = (float) $lrReceipt->total;
                $from = trim((string) $lrReceipt->tr_from_name);
                $to = trim((string) $lrReceipt->tr_to_name);
                $invDate = $lrReceipt->invoice_date ? Carbon::parse($lrReceipt->invoice_date)->toDateString() : null;

                if (($from !== '' || $to !== '') && $amt > $maxAmount) {
                    $maxAmount = $amt;
                    $bestMatch = [
                        'from' => $from ?: null,
                        'to' => $to ?: null,
                        'source' => "LR Receipt #{$lrReceipt->invoice_number} (ID: {$lrReceipt->id}, Amount: ".($amt / 100).')',
                        'max_amount' => $amt,
                        'invoice_date' => $invDate,
                    ];
                }
            }
        }
    }

    return $bestMatch ?? ['from' => null, 'to' => null, 'source' => null, 'max_amount' => 0, 'invoice_date' => null];
}

$createdCount = 0;
$updatedCount = 0;
$errorCount = 0;

foreach ($records as $index => $row) {
    $challanNo = (string) $row['challan_no'];
    $paddedChallan = str_pad($challanNo, 3, '0', STR_PAD_LEFT);
    $paidTo = trim($row['paid_to']);
    $hire = (int) $row['hire'];
    $advance = (int) $row['advance'];
    $lrNo = trim((string) $row['lr_no']);
    $lorryNo = trim((string) $row['lorry_no']);

    // Resolve From and To from Invoice Receipts
    $resolved = resolveFromToForLrNumbers($lrNo, $companyId);
    $from = $resolved['from'];
    $to = $resolved['to'];
    $source = $resolved['source'] ?? 'No match found';

    // Check if Lorry Receipt invoice already exists
    $existing = Invoice::query()
        ->where('company_id', $companyId)
        ->where('template_name', 'lorry_receipt')
        ->where(function ($q) use ($challanNo, $paddedChallan) {
            $q->where('invoice_number', $challanNo)
                ->orWhere('invoice_number', $paddedChallan);
        })
        ->first();

    if ($existing !== null) {
        // --- 1. UPDATE EXISTING RECORD ---
        try {
            DB::beginTransaction();

            $updateData = [
                'tr_lorry_no' => $lorryNo,
                'tr_truck_no' => $lorryNo,
                'tr_received_no_bilties' => $lrNo,
            ];

            if ($from !== null && $from !== '') {
                $updateData['tr_from_name'] = $from;
            }
            if ($to !== null && $to !== '') {
                $updateData['tr_to_name'] = $to;
            }

            $existing->forceFill($updateData)->saveQuietly();

            DB::commit();

            echo "[UPDATED] Challan #{$challanNo} (Invoice ID: {$existing->id})\n";
            echo "   -> Lorry No: '{$lorryNo}'\n";
            echo "   -> LR No: '{$lrNo}'\n";
            echo "   -> From: '".($existing->tr_from_name ?? '—')."', To: '".($existing->tr_to_name ?? '—')."'\n";
            echo "   -> Resolved Via: {$source}\n\n";

            $updatedCount++;
        } catch (Throwable $e) {
            DB::rollBack();
            echo "[!] Error updating Challan {$challanNo}: ".$e->getMessage()."\n\n";
            $errorCount++;
        }
    } else {
        // --- 2. CREATE NEW RECORD ---
        try {
            DB::beginTransaction();

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

            // 3. Financial amounts (minor units / paise)
            $hirePaise = $hire * 100;
            $advPaise = $advance * 100;
            $duePaise = max(0, $hirePaise - $advPaise);
            $balanceRs = (string) max(0, $hire - $advance);

            // Use resolved invoice date if available, or current date
            $invoiceDate = $resolved['invoice_date'] ?: Carbon::now()->toDateString();

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
                'tr_lorry_no' => $lorryNo,
                'tr_truck_no' => $lorryNo,
                'tr_from_name' => $from,
                'tr_to_name' => $to,
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
            echo "[CREATED] Challan #{$challanNo} (Invoice ID: {$invoice->id})\n";
            echo "   -> Paid To: '{$paidTo}' | Hire: Rs.{$hire} | Adv: Rs.{$advance}\n";
            echo "   -> Lorry No: '{$lorryNo}'\n";
            echo "   -> LR No: '{$lrNo}'\n";
            echo "   -> From: '".($invoice->tr_from_name ?? '—')."', To: '".($invoice->tr_to_name ?? '—')."'\n";
            echo "   -> Resolved Via: {$source}\n";
            echo '   -> Bill ID: '.($invoice->tr_bill_id ?? 'N/A').' | Adv Payment ID: '.($invoice->tr_advance_payment_id ?? 'N/A')."\n\n";

            $createdCount++;
        } catch (Throwable $e) {
            DB::rollBack();
            echo "[!] Error creating Challan {$challanNo}: ".$e->getMessage()."\n".$e->getTraceAsString()."\n\n";
            $errorCount++;
        }
    }
}

echo "========================================================================\n";
echo "SYNC COMPLETED!\n";
echo "   Updated: {$updatedCount}\n";
echo "   Created: {$createdCount}\n";
echo "   Errors:  {$errorCount}\n";
echo '   Total:   '.count($records)."\n";
echo "========================================================================\n";
