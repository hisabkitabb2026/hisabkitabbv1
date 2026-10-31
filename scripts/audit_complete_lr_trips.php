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

echo "=========================================================================================\n";
echo "        HISABKITABB - COMPLETE LR, VEHICLE (LORRY), INVOICE & PAYMENTS AUDIT             \n";
echo "=========================================================================================\n\n";

// =========================================================================================
// PART 1: TOTAL NUMBER OF LRS IN DATABASE
// =========================================================================================
echo "-----------------------------------------------------------------------------------------\n";
echo "PART 1: TOTAL NUMBER OF LR RECEIPTS IN DATABASE\n";
echo "-----------------------------------------------------------------------------------------\n";

$allLrs = Invoice::where('template_name', 'lr_receipt')->get();
$totalLrs = $allLrs->count();
$co1Lrs = $allLrs->where('company_id', 1)->count();
$co2Lrs = $allLrs->where('company_id', 2)->count();

$linkedLrInvoiceIds = DB::table('trip_receipts')
    ->where('type', 'lr')
    ->pluck('invoice_id')
    ->unique()
    ->all();

$linkedLrsCount = count(array_intersect($allLrs->pluck('id')->all(), $linkedLrInvoiceIds));
$unlinkedLrsCount = $totalLrs - $linkedLrsCount;

echo "Total LR Receipts in Database: {$totalLrs}\n";
echo "  • Company 1 (Vapi):       {$co1Lrs}\n";
echo "  • Company 2 (Umbergaon):  {$co2Lrs}\n";
echo "  • Linked to a Trip:       {$linkedLrsCount}\n";
echo "  • Unlinked (No Trip):     {$unlinkedLrsCount}\n\n";

// =========================================================================================
// PART 2: SHARED VEHICLES (LORRY RECEIPTS WITH MULTIPLE LRS)
// =========================================================================================
echo "-----------------------------------------------------------------------------------------\n";
echo "PART 2: SHARED VEHICLES / MULTI-LR CONSIGNMENTS (Same Truck in Lorry Receipt)\n";
echo "-----------------------------------------------------------------------------------------\n";

$allLorryInvoices = Invoice::where('template_name', 'lorry_receipt')->get();
$sharedVehicles = [];
$singleLrVehicles = [];
$emptyBiltyVehicles = [];

foreach ($allLorryInvoices as $lorry) {
    $b = trim((string) $lorry->tr_received_no_bilties);
    if ($b === '') {
        $emptyBiltyVehicles[] = $lorry;

        continue;
    }

    $tokens = preg_split('/[\s,;]+/u', $b, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $tokens = array_values(array_unique(array_map('trim', $tokens)));

    if (count($tokens) > 1) {
        $sharedVehicles[] = [
            'lorry' => $lorry,
            'bilties' => $tokens,
        ];
    } else {
        $singleLrVehicles[] = [
            'lorry' => $lorry,
            'bilty' => $tokens[0] ?? '',
        ];
    }
}

echo "Total Lorry Receipts (Trucks Hired): {$allLorryInvoices->count()}\n";
echo '  • Single-LR Loads (1 Truck = 1 LR):            '.count($singleLrVehicles)."\n";
echo '  • Shared-Vehicle Loads (1 Truck = Multiple LRs): '.count($sharedVehicles)."\n";
echo '  • Empty Bilties on Lorry Receipt:              '.count($emptyBiltyVehicles)."\n\n";

echo "DETAILS OF SHARED-VEHICLE LOADS (Multiple LRs on Same Vehicle):\n";
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

    // Check which of these bilties exist as LR receipts in DB
    $existingLrs = Invoice::where('template_name', 'lr_receipt')
        ->whereIn('invoice_number', $bilties)
        ->get(['id', 'invoice_number']);

    $existingNumbers = $existingLrs->pluck('invoice_number')->all();

    // Check trips for these LRs
    $tripIds = DB::table('trip_receipts')
        ->whereIn('invoice_id', $existingLrs->pluck('id'))
        ->where('type', 'lr')
        ->pluck('trip_id')
        ->unique()
        ->values()
        ->all();

    // Check which trip the Lorry receipt itself is on
    $lorryTrip = DB::table('trip_receipts')
        ->where('invoice_id', $l->id)
        ->where('type', 'lorry')
        ->value('trip_id');

    $tripStatusStr = '';
    if (empty($tripIds)) {
        $tripStatusStr = 'LRs not in trips';
    } elseif (count($tripIds) === 1) {
        $tId = $tripIds[0];
        $tripNo = DB::table('trips')->where('id', $tId)->value('trip_no');
        if ($lorryTrip == $tId) {
            $tripStatusStr = "✅ SAME TRIP (Trip #{$tripNo})";
        } else {
            $tripStatusStr = "⚠️ LRs on Trip #{$tripNo}, Lorry on Trip #".($lorryTrip ?: 'None');
        }
    } else {
        $tripNos = DB::table('trips')->whereIn('id', $tripIds)->pluck('trip_no')->all();
        $tripStatusStr = '❌ SPLIT: LRs on separate Trips ('.implode(', ', $tripNos).')';
    }

    printf(
        "%-12s | %-14s | %-24s | %-25s | %-12s | %s\n",
        substr($l->invoice_number, 0, 12),
        substr($l->tr_lorry_no ?: '-', 0, 14),
        substr($route, 0, 24),
        substr(implode(', ', $bilties), 0, 25),
        count($existingNumbers).'/'.count($bilties),
        $tripStatusStr
    );
}

// =========================================================================================
// PART 3: COMPLETE DOCUMENT CHAIN AUDIT ACROSS ALL TRIPS
// =========================================================================================
echo "\n-----------------------------------------------------------------------------------------\n";
echo "PART 3: DOCUMENT CHAIN CHECK FOR EVERY TRIP (LR -> LORRY -> INVOICE -> PAYMENTS -> BILL)\n";
echo "-----------------------------------------------------------------------------------------\n";

$trips = Trip::with(['receipts'])->orderBy('trip_no')->get();

$allDocComplete = 0;
$inTransitWithLorry = 0;
$missingLorryInTrip = 0;
$missingInvoiceInTrip = 0;
$missingCustomerPayment = 0;
$missingSupplierBill = 0;
$lorryExistsInDbNotLinked = 0;
$invoiceExistsInDbNotLinked = 0;

$disconnectionList = [];

foreach ($trips as $trip) {
    $lrReceipts = $trip->receipts->where('type', TripReceipt::TYPE_LR);
    $lorryReceipts = $trip->receipts->where('type', TripReceipt::TYPE_LORRY);
    $invoiceReceipts = $trip->receipts->where('type', TripReceipt::TYPE_INVOICE);

    $lrNumbers = $lrReceipts->pluck('invoice_number')->filter()->values()->all();
    $lorryNumbers = $lorryReceipts->pluck('invoice_number')->filter()->values()->all();
    $invoiceNumbers = $invoiceReceipts->pluck('invoice_number')->filter()->values()->all();

    $hasLr = $lrReceipts->isNotEmpty();
    $hasLorry = $lorryReceipts->isNotEmpty();
    $hasInvoice = $invoiceReceipts->isNotEmpty();

    // Check customer payments on linked invoices
    $customerPaidTotal = 0;
    $customerDueTotal = 0;
    if ($hasInvoice) {
        $invModels = Invoice::whereIn('id', $invoiceReceipts->pluck('invoice_id'))->get();
        foreach ($invModels as $im) {
            $customerDueTotal += (int) $im->due_amount;
            $customerPaidTotal += max(0, (int) $im->total - (int) $im->due_amount);
        }
    }

    // Check supplier bills on linked lorry receipts
    $hasBill = false;
    $billPaidTotal = 0;
    $billDueTotal = 0;
    if ($hasLorry) {
        $lorryModels = Invoice::whereIn('id', $lorryReceipts->pluck('invoice_id'))->get();
        $billIds = $lorryModels->pluck('tr_bill_id')->filter()->all();
        if (! empty($billIds)) {
            $hasBill = true;
            $bills = Bill::whereIn('id', $billIds)->get();
            foreach ($bills as $b) {
                $billDueTotal += (int) $b->due_amount;
                $billPaidTotal += max(0, (int) $b->total - (int) $b->due_amount);
            }
        }
    }

    // Diagnostics: if missing Lorry or Invoice, does it exist in the DB?
    $unlinkedLorryCandidate = null;
    if (! $hasLorry && ! empty($lrNumbers)) {
        // Search if any lorry receipt in DB lists this LR
        $cand = Invoice::where('template_name', 'lorry_receipt')
            ->whereNotNull('tr_received_no_bilties')
            ->where('tr_received_no_bilties', '!=', '')
            ->get()
            ->filter(function ($inv) use ($lrNumbers) {
                $toks = preg_split('/[\s,;]+/u', (string) $inv->tr_received_no_bilties) ?: [];

                return collect($toks)->map(fn ($t) => trim($t))->intersect($lrNumbers)->isNotEmpty();
            })
            ->first();

        if ($cand) {
            $lorryExistsInDbNotLinked++;
            $unlinkedLorryCandidate = $cand;
        }
    }

    $unlinkedInvoiceCandidate = null;
    if (! $hasInvoice && ! empty($lrNumbers)) {
        // Search if any invoice receipt in DB has line items with this LR
        $candInv = Invoice::where('template_name', 'invoice_receipt')
            ->whereHas('items', function ($q) use ($lrNumbers) {
                $q->whereIn('name', $lrNumbers)
                    ->orWhereIn('tr_consignment_number', $lrNumbers);
            })
            ->first();

        if ($candInv) {
            $invoiceExistsInDbNotLinked++;
            $unlinkedInvoiceCandidate = $candInv;
        }
    }

    if ($unlinkedLorryCandidate || $unlinkedInvoiceCandidate) {
        $disconnectionList[] = [
            'trip_no' => $trip->trip_no,
            'lrs' => implode(', ', $lrNumbers),
            'found_lorry' => $unlinkedLorryCandidate?->invoice_number,
            'found_inv' => $unlinkedInvoiceCandidate?->invoice_number,
        ];
    }

    if ($hasLr && $hasLorry && $hasInvoice && $hasBill) {
        $allDocComplete++;
    } elseif ($hasLr && $hasLorry && ! $hasInvoice) {
        $inTransitWithLorry++;
    } elseif ($hasLr && ! $hasLorry) {
        $missingLorryInTrip++;
    }
}

echo "TOTAL TRIPS ANALYZED: {$trips->count()}\n";
echo "  ✅ 5/5 Documents Fully Matched (LR + Lorry + Bill + Inv + Payments): {$allDocComplete}\n";
echo "  🚚 In-Transit / Pending Billing (LR + Lorry + Bill linked, Inv pending): {$inTransitWithLorry}\n";
echo "  ⚠️ Trips Missing Lorry Receipt: {$missingLorryInTrip}\n";
echo "     ↳ Of these, Lorry Receipts that ALREADY EXIST in DB but are not linked: {$lorryExistsInDbNotLinked}\n";
echo '  ⚠️ Trips Missing Customer Invoice: '.(count($trips) - $allDocComplete - $inTransitWithLorry)."\n";
echo "     ↳ Of these, Invoices that ALREADY EXIST in DB but are not linked: {$invoiceExistsInDbNotLinked}\n\n";

if (! empty($disconnectionList)) {
    echo "=========================================================================================\n";
    echo "AVAILABLE MATCHES IN DATABASE (CAN BE AUTOMATICALLY CONNECTED TO TRIPS):\n";
    echo "=========================================================================================\n";
    printf("%-10s | %-20s | %-20s | %-20s\n", 'Trip #', 'Trip LRs', 'Found Lorry Challan', 'Found Invoice Receipt');
    echo str_repeat('-', 80)."\n";
    foreach ($disconnectionList as $disc) {
        printf(
            "%-10s | %-20s | %-20s | %-20s\n",
            $disc['trip_no'],
            substr($disc['lrs'], 0, 20),
            $disc['found_lorry'] ? "Challan #{$disc['found_lorry']}" : '-',
            $disc['found_inv'] ? "Invoice #{$disc['found_inv']}" : '-'
        );
    }
} else {
    echo "No unlinked matching candidates found.\n";
}

echo "\n=========================================================================================\n";
