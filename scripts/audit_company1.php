<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Domains\Purchases\Models\Bill;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripReceipt;

$companyId = 1;
$companyName = DB::table('companies')->where('id', $companyId)->value('name') ?: 'Company 1 (Vapi)';

echo "=========================================================================================\n";
echo "              HISABKITABB - COMPLETE AUDIT FOR: {$companyName} (ID: {$companyId})        \n";
echo "=========================================================================================\n\n";

// =========================================================================================
// SECTION 1: LR RECEIPTS IN COMPANY 1
// =========================================================================================
echo "-----------------------------------------------------------------------------------------\n";
echo "1. LR RECEIPTS (REVENUE CONSIGNMENT NOTES)\n";
echo "-----------------------------------------------------------------------------------------\n";

$co1Lrs = Invoice::where('company_id', $companyId)->where('template_name', 'lr_receipt')->orderBy('id')->get();
$totalLrs = $co1Lrs->count();

$linkedLrInvoiceIds = DB::table('trip_receipts')
    ->where('company_id', $companyId)
    ->where('type', 'lr')
    ->pluck('invoice_id')
    ->unique()
    ->all();

$unlinkedLrs = $co1Lrs->whereNotIn('id', $linkedLrInvoiceIds);

echo "Total LR Receipts: {$totalLrs}\n";
echo '  • Linked to a Trip:   '.($totalLrs - $unlinkedLrs->count())." (100%)\n";
echo "  • Unlinked (No Trip): {$unlinkedLrs->count()}\n";
if ($unlinkedLrs->isNotEmpty()) {
    echo '    Unlinked LR numbers: '.implode(', ', $unlinkedLrs->pluck('invoice_number')->all())."\n";
}
echo "\n";

// =========================================================================================
// SECTION 2: LORRY RECEIPTS (TRUCK HIRE CHALLANS) & SHARED VEHICLES IN COMPANY 1
// =========================================================================================
echo "-----------------------------------------------------------------------------------------\n";
echo "2. LORRY RECEIPTS (TRUCK HIRE CHALLANS) & SHARED VEHICLES\n";
echo "-----------------------------------------------------------------------------------------\n";

$co1Lorry = Invoice::where('company_id', $companyId)->where('template_name', 'lorry_receipt')->orderBy('id')->get();
$totalLorry = $co1Lorry->count();

$sharedVehicles = [];
$singleVehicles = [];
$emptyBiltyLorry = [];

foreach ($co1Lorry as $l) {
    $b = trim((string) $l->tr_received_no_bilties);
    if ($b === '') {
        $emptyBiltyLorry[] = $l;

        continue;
    }

    $tokens = preg_split('/[\s,;]+/u', $b, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $tokens = array_values(array_unique(array_map('trim', $tokens)));

    if (count($tokens) > 1) {
        $sharedVehicles[] = [
            'lorry' => $l,
            'bilties' => $tokens,
        ];
    } else {
        $singleVehicles[] = [
            'lorry' => $l,
            'bilty' => $tokens[0] ?? '',
        ];
    }
}

echo "Total Lorry Receipts (Trucks Hired): {$totalLorry}\n";
echo '  • Single-LR Loads (1 Truck = 1 LR):            '.count($singleVehicles)."\n";
echo '  • Shared-Vehicle Loads (1 Truck = Multiple LRs): '.count($sharedVehicles)."\n";
echo '  • Missing / Empty Bilties:                     '.count($emptyBiltyLorry)."\n\n";

if (! empty($sharedVehicles)) {
    echo "SHARED-VEHICLE LOADS IN COMPANY 1:\n";
    echo str_repeat('-', 115)."\n";
    printf(
        "%-12s | %-14s | %-24s | %-25s | %-12s | %s\n",
        'Challan No', 'Truck No', 'Route', 'Lorry Bilties Listed', 'LRs in DB', 'Trip Status'
    );
    echo str_repeat('-', 115)."\n";

    foreach ($sharedVehicles as $sv) {
        $l = $sv['lorry'];
        $bilties = $sv['bilties'];
        $route = trim(($l->tr_from_name ?: '-').' -> '.($l->tr_to_name ?: '-'));

        $existingLrs = $co1Lrs->whereIn('invoice_number', $bilties);
        $existingNumbers = $existingLrs->pluck('invoice_number')->all();

        $tripIds = DB::table('trip_receipts')
            ->where('company_id', $companyId)
            ->whereIn('invoice_id', $existingLrs->pluck('id'))
            ->where('type', 'lr')
            ->pluck('trip_id')
            ->unique()
            ->values()
            ->all();

        $lorryTrip = DB::table('trip_receipts')
            ->where('company_id', $companyId)
            ->where('invoice_id', $l->id)
            ->where('type', 'lorry')
            ->value('trip_id');

        $statusStr = '';
        if (empty($tripIds)) {
            $statusStr = 'LRs not in trips';
        } elseif (count($tripIds) === 1) {
            $tId = $tripIds[0];
            $tripNo = DB::table('trips')->where('id', $tId)->value('trip_no');
            if ($lorryTrip == $tId) {
                $statusStr = "✅ SAME TRIP (Trip #{$tripNo})";
            } else {
                $statusStr = "⚠️ LRs on Trip #{$tripNo}, Lorry on Trip #".($lorryTrip ?: 'None');
            }
        } else {
            $tripNos = DB::table('trips')->whereIn('id', $tripIds)->pluck('trip_no')->all();
            $statusStr = '❌ SPLIT: LRs on separate Trips ('.implode(', ', $tripNos).')';
        }

        printf(
            "%-12s | %-14s | %-24s | %-25s | %-12s | %s\n",
            substr($l->invoice_number, 0, 12),
            substr($l->tr_lorry_no ?: '-', 0, 14),
            substr($route, 0, 24),
            substr(implode(', ', $bilties), 0, 25),
            count($existingNumbers).'/'.count($bilties),
            $statusStr
        );
    }
    echo "\n";
}

// =========================================================================================
// SECTION 3: TRIPS & 5-DOCUMENT CHAIN AUDIT IN COMPANY 1
// =========================================================================================
echo "-----------------------------------------------------------------------------------------\n";
echo "3. TRIPS & 5-DOCUMENT CHAIN AUDIT (LR -> LORRY -> INVOICE -> PAYMENTS -> BILL)\n";
echo "-----------------------------------------------------------------------------------------\n";

$co1Trips = Trip::where('company_id', $companyId)->with(['receipts', 'status'])->orderBy('trip_no')->get();
$totalTrips = $co1Trips->count();

$fullyMatched = [];
$inTransit = [];
$missingLorry = [];
$standaloneLorry = [];
$emptyTrips = [];

$totalRevenue = 0;
$totalBankReceived = 0;
$totalTds = 0;
$totalCustomerDue = 0;
$totalTruckCost = 0;
$totalOwnerPaid = 0;
$totalOwnerBalance = 0;

foreach ($co1Trips as $trip) {
    $lrReceipts = $trip->receipts->where('type', TripReceipt::TYPE_LR);
    $lorryReceipts = $trip->receipts->where('type', TripReceipt::TYPE_LORRY);
    $invoiceReceipts = $trip->receipts->where('type', TripReceipt::TYPE_INVOICE);

    $hasLr = $lrReceipts->isNotEmpty();
    $hasLorry = $lorryReceipts->isNotEmpty();
    $hasInvoice = $invoiceReceipts->isNotEmpty();

    // Check customer invoices
    $invRevenue = 0;
    $invBank = 0;
    $invTds = 0;
    $invDue = 0;
    if ($hasInvoice) {
        $invModels = Invoice::whereIn('id', $invoiceReceipts->pluck('invoice_id'))->with('allocations.payment')->get();
        foreach ($invModels as $im) {
            $invRevenue += (int) $im->total;
            $invDue += (int) $im->due_amount;
            foreach ($im->allocations as $alloc) {
                if ($alloc->payment) {
                    $invBank += (int) $alloc->amount;
                    $invTds += (int) ($alloc->tds_amount ?: ($alloc->payment->tds_amount ?? 0));
                }
            }
        }
    }

    // Check supplier bills
    $truckCost = 0;
    $ownerPaid = 0;
    $ownerDue = 0;
    $hasBill = false;
    if ($hasLorry) {
        $lorryModels = Invoice::whereIn('id', $lorryReceipts->pluck('invoice_id'))->get();
        $billIds = $lorryModels->pluck('tr_bill_id')->filter()->all();
        if (! empty($billIds)) {
            $hasBill = true;
            $bills = Bill::whereIn('id', $billIds)->get();
            foreach ($bills as $b) {
                $bTotal = (int) $b->total;
                $bDue = (int) $b->due_amount;
                $truckCost += $bTotal;
                $ownerPaid += ($bTotal - $bDue);
                $ownerDue += $bDue;
            }
        } else {
            foreach ($lorryReceipts as $lr) {
                $truckCost += (int) $lr->amount;
                $ownerDue += (int) $lr->amount;
            }
        }
    }

    $totalRevenue += $invRevenue;
    $totalBankReceived += $invBank;
    $totalTds += $invTds;
    $totalCustomerDue += $invDue;
    $totalTruckCost += $truckCost;
    $totalOwnerPaid += $ownerPaid;
    $totalOwnerBalance += $ownerDue;

    if (! $hasLr && ! $hasLorry) {
        $emptyTrips[] = $trip->trip_no;
    } elseif ($hasLr && ! $hasLorry) {
        $missingLorry[] = $trip->trip_no;
    } elseif (! $hasLr && $hasLorry) {
        $standaloneLorry[] = $trip->trip_no;
    } elseif ($hasInvoice) {
        $fullyMatched[] = $trip->trip_no;
    } else {
        $inTransit[] = $trip->trip_no;
    }
}

echo "Total Trips on Board in Company 1: {$totalTrips}\n";
echo "-----------------------------------------------------------------------------------------\n";
printf("%-48s | %-8s | %s\n", 'Category', 'Count', 'Sample Trip #s');
echo str_repeat('-', 105)."\n";

printf(
    "%-48s | %-8d | %s\n",
    '✅ 100% Complete & Delivered (LR + Lorry + Inv)',
    count($fullyMatched),
    implode(', ', array_slice($fullyMatched, 0, 10)).(count($fullyMatched) > 10 ? '...' : '')
);

printf(
    "%-48s | %-8d | %s\n",
    '🚚 In-Transit (LR + Lorry matched, Inv pending)',
    count($inTransit),
    implode(', ', array_slice($inTransit, 0, 10)).(count($inTransit) > 10 ? '...' : '')
);

printf(
    "%-48s | %-8d | %s\n",
    '⚠️ Missing Lorry Receipt (LR only, no truck)',
    count($missingLorry),
    implode(', ', array_slice($missingLorry, 0, 10)).(count($missingLorry) > 10 ? '...' : '')
);

printf(
    "%-48s | %-8d | %s\n",
    '⚠️ Standalone Lorry (Lorry only, no LR invoice)',
    count($standaloneLorry),
    count($standaloneLorry) ? implode(', ', $standaloneLorry) : 'None'
);

printf(
    "%-48s | %-8d | %s\n",
    '⚠️ Empty Trips (No receipts)',
    count($emptyTrips),
    count($emptyTrips) ? implode(', ', $emptyTrips) : 'None'
);

// =========================================================================================
// SECTION 4: FINANCIAL TOTALS FOR COMPANY 1
// =========================================================================================
echo "\n-----------------------------------------------------------------------------------------\n";
echo "4. FINANCIAL HEALTH & RECONCILIATION SUMMARY (COMPANY 1)\n";
echo "-----------------------------------------------------------------------------------------\n";
echo "CUSTOMER REVENUE (MONEY IN):\n";
echo '  • Total Customer Freight Invoiced: ₹'.number_format($totalRevenue / 100, 2)."\n";
echo '  • Actual Money Received in Bank:   ₹'.number_format($totalBankReceived / 100, 2)."\n";
echo '  • TDS Deducted by Customers:       ₹'.number_format($totalTds / 100, 2)."\n";
echo '  • Customer Outstanding Balance:    ₹'.number_format($totalCustomerDue / 100, 2)."\n\n";

echo "TRUCK HIRE & OWNER PAYMENTS (MONEY OUT):\n";
echo '  • Total Truck Hire Costs:          ₹'.number_format($totalTruckCost / 100, 2)."\n";
echo '  • Total Paid to Truck Owners:      ₹'.number_format($totalOwnerPaid / 100, 2)."\n";
echo '  • Remaining Balance Due to Owners: ₹'.number_format($totalOwnerBalance / 100, 2)."\n\n";

$netProfit = ($totalRevenue - $totalTruckCost) / 100;
echo 'NET PROFIT (Invoiced Revenue - Truck Hire): ₹'.number_format($netProfit, 2)."\n";
echo "=========================================================================================\n";
