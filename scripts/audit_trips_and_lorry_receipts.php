<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Modules\Trips\Models\Trip;

echo "=========================================================================================\n";
echo "           HISABKITABB / INVOICESHELF - TRANSPORT & TRIPS AUDIT REPORT                   \n";
echo "=========================================================================================\n\n";

// -----------------------------------------------------------------------------------------
// SECTION 1: LORRY RECEIPTS WITH MISSING / EMPTY 'tr_received_no_bilties'
// -----------------------------------------------------------------------------------------
echo "-----------------------------------------------------------------------------------------\n";
echo "1. LORRY RECEIPTS WITH MISSING / EMPTY 'Received No of Bilties' (tr_received_no_bilties)\n";
echo "-----------------------------------------------------------------------------------------\n";

$missingBilties = DB::table('invoices')
    ->leftJoin('trip_receipts', function ($join) {
        $join->on('trip_receipts.invoice_id', '=', 'invoices.id')
            ->where('trip_receipts.type', '=', 'lorry');
    })
    ->leftJoin('trips', 'trips.id', '=', 'trip_receipts.trip_id')
    ->where('invoices.template_name', 'lorry_receipt')
    ->where(function ($q) {
        $q->whereNull('invoices.tr_received_no_bilties')
            ->orWhere('invoices.tr_received_no_bilties', '=', '')
            ->orWhereRaw("TRIM(invoices.tr_received_no_bilties) = ''");
    })
    ->select([
        'invoices.id',
        'invoices.invoice_number',
        'invoices.invoice_date',
        'invoices.tr_lorry_no',
        'invoices.tr_from_name',
        'invoices.tr_to_name',
        'invoices.tr_paid_to',
        'invoices.tr_lorry_hire_amount',
        'invoices.total',
        'invoices.tr_received_no_bilties',
        'trips.trip_no',
    ])
    ->orderBy('invoices.id')
    ->get();

$totalLorryReceipts = DB::table('invoices')->where('template_name', 'lorry_receipt')->count();
$populatedLorryReceipts = DB::table('invoices')
    ->where('template_name', 'lorry_receipt')
    ->whereNotNull('tr_received_no_bilties')
    ->whereRaw("TRIM(tr_received_no_bilties) != ''")
    ->count();

echo "Total Lorry Receipts: {$totalLorryReceipts}\n";
echo "  - With 'Received No of Bilties': {$populatedLorryReceipts}\n";
echo "  - Missing 'Received No of Bilties': {$missingBilties->count()}\n\n";

if ($missingBilties->isEmpty()) {
    echo "  [✓] All Lorry Receipts have 'Received No of Bilties' filled in!\n\n";
} else {
    printf(
        "%-6s | %-12s | %-12s | %-14s | %-25s | %-20s | %-10s | %-10s\n",
        'Inv ID', 'Challan No', 'Date', 'Lorry No', 'Route (From -> To)', 'Paid To', 'Hire (Rs)', 'Linked Trip'
    );
    echo str_repeat('-', 125)."\n";
    foreach ($missingBilties as $lr) {
        $route = trim(($lr->tr_from_name ?: '-').' -> '.($lr->tr_to_name ?: '-'));
        $hire = $lr->tr_lorry_hire_amount ?: ($lr->total ? $lr->total / 100 : 0);
        $tripStr = $lr->trip_no ? "Trip #{$lr->trip_no}" : '[UNLINKED]';
        printf(
            "%-6d | %-12s | %-12s | %-14s | %-25s | %-20s | %-10s | %-10s\n",
            $lr->id,
            $lr->invoice_number,
            substr((string) $lr->invoice_date, 0, 10),
            $lr->tr_lorry_no ?: '-',
            substr($route, 0, 25),
            substr((string) $lr->tr_paid_to, 0, 20),
            number_format((float) $hire, 0),
            $tripStr
        );
    }
    echo "\n";
}

// -----------------------------------------------------------------------------------------
// SECTION 2: ALL LORRY RECEIPTS IN DATABASE (Overview)
// -----------------------------------------------------------------------------------------
echo "-----------------------------------------------------------------------------------------\n";
echo "2. ALL LORRY RECEIPTS IN DATABASE (Overview)\n";
echo "-----------------------------------------------------------------------------------------\n";
$allLorry = DB::table('invoices')
    ->leftJoin('trip_receipts', function ($join) {
        $join->on('trip_receipts.invoice_id', '=', 'invoices.id')
            ->where('trip_receipts.type', '=', 'lorry');
    })
    ->leftJoin('trips', 'trips.id', '=', 'trip_receipts.trip_id')
    ->where('invoices.template_name', 'lorry_receipt')
    ->select([
        'invoices.id',
        'invoices.invoice_number',
        'invoices.invoice_date',
        'invoices.tr_lorry_no',
        'invoices.tr_received_no_bilties',
        'trips.trip_no',
    ])
    ->orderBy('invoices.id')
    ->get();

printf(
    "%-6s | %-12s | %-12s | %-14s | %-25s | %-10s\n",
    'Inv ID', 'Challan No', 'Date', 'Lorry No', 'Received Bilties', 'Linked Trip'
);
echo str_repeat('-', 95)."\n";
foreach ($allLorry as $l) {
    $bilties = trim((string) $l->tr_received_no_bilties);
    $biltiesDisplay = ($bilties === '') ? '⚠️ [MISSING/EMPTY]' : $bilties;
    $tripStr = $l->trip_no ? "Trip #{$l->trip_no}" : '[UNLINKED]';
    printf(
        "%-6d | %-12s | %-12s | %-14s | %-25s | %-10s\n",
        $l->id,
        $l->invoice_number,
        substr((string) $l->invoice_date, 0, 10),
        $l->tr_lorry_no ?: '-',
        substr($biltiesDisplay, 0, 25),
        $tripStr
    );
}
echo "\n";

// -----------------------------------------------------------------------------------------
// SECTION 3: TRIPS MODULE AUDIT
// -----------------------------------------------------------------------------------------
echo "-----------------------------------------------------------------------------------------\n";
echo "3. TRIPS AUDIT: CHECKING ALL TRIPS & LINKED RECEIPTS (LR, LORRY, INVOICE)\n";
echo "-----------------------------------------------------------------------------------------\n";

$trips = Trip::with(['status', 'receipts'])->orderBy('trip_no')->get();

echo "Total Trips Found: {$trips->count()}\n\n";

foreach ($trips as $trip) {
    $statusName = $trip->status?->name ?? 'Unknown';
    $lrReceipts = $trip->receipts->where('type', 'lr');
    $lorryReceipts = $trip->receipts->where('type', 'lorry');
    $invoiceReceipts = $trip->receipts->where('type', 'invoice');

    $lrNumbers = $lrReceipts->pluck('invoice_number')->filter()->values()->all();
    $lorryNumbers = $lorryReceipts->pluck('invoice_number')->filter()->values()->all();
    $invoiceNumbers = $invoiceReceipts->pluck('invoice_number')->filter()->values()->all();

    // Fetch the actual Lorry Receipt invoices to inspect tr_received_no_bilties
    $lorryInvoiceIds = $lorryReceipts->pluck('invoice_id')->all();
    $lorryInvoices = DB::table('invoices')->whereIn('id', $lorryInvoiceIds)->get();

    $lorryBilties = [];
    foreach ($lorryInvoices as $li) {
        $lorryBilties[] = [
            'challan' => $li->invoice_number,
            'bilties' => trim((string) $li->tr_received_no_bilties),
            'lorry_no' => $li->tr_lorry_no,
        ];
    }

    // Health / Correctness evaluation
    $issues = [];
    if ($lrReceipts->isEmpty()) {
        $issues[] = '⚠️ NO LR RECEIPT (Revenue bilty missing from this trip)';
    }
    if ($lorryReceipts->isEmpty()) {
        $issues[] = '⚠️ NO LORRY RECEIPT (Truck hire challan missing)';
    } elseif ($lorryReceipts->count() > 1) {
        $issues[] = 'ℹ️ Multiple Lorry Receipts linked ('.implode(', ', $lorryNumbers).')';
    }

    foreach ($lorryBilties as $lb) {
        if ($lb['bilties'] === '') {
            $issues[] = "❌ Lorry Challan #{$lb['challan']} has EMPTY 'Received No of Bilties'";
        } else {
            // Check if bilties match any of this trip's LRs
            $tokens = preg_split('/[\s,;]+/u', $lb['bilties'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $tokens = array_map('trim', $tokens);
            $matched = array_intersect($tokens, $lrNumbers);
            if (empty($matched) && ! empty($lrNumbers)) {
                $issues[] = "❌ BILTY MISMATCH: Challan #{$lb['challan']} lists bilties '{$lb['bilties']}' but trip has LRs [".implode(', ', $lrNumbers).']';
            }
        }
    }

    if ($invoiceReceipts->isEmpty()) {
        $issues[] = 'ℹ️ No Invoice Receipt linked yet (Customer freight invoice pending)';
    }

    $health = empty($issues) ? '✅ 100% HEALTHY & MATCHED' : implode("\n      ", $issues);

    echo "=========================================================================================\n";
    echo " TRIP #{$trip->trip_no} (ID: {$trip->id}) | Status: [{$statusName}] | Date: ".($trip->pickup_date?->format('Y-m-d') ?: '-')."\n";
    echo ' Route: '.($trip->from_city ?: '-').' -> '.($trip->to_city ?: '-').' | Lorry No: '.($trip->lorry_no ?: '-')."\n";
    echo "-----------------------------------------------------------------------------------------\n";
    echo '  1. LR Receipts (Revenue):      '.($lrNumbers ? implode(', ', $lrNumbers).' (Count: '.count($lrNumbers).')' : 'NONE')."\n";

    $lorryStr = 'NONE';
    if (! empty($lorryBilties)) {
        $parts = [];
        foreach ($lorryBilties as $lb) {
            $biltyVal = $lb['bilties'] === '' ? '[EMPTY/MISSING]' : "'{$lb['bilties']}'";
            $parts[] = "Challan #{$lb['challan']} (Truck: {$lb['lorry_no']}, Received Bilties: {$biltyVal})";
        }
        $lorryStr = implode('; ', $parts);
    }
    echo "  2. Lorry Receipts (Cost):      {$lorryStr}\n";
    echo '  3. Invoice Receipts (Billing): '.($invoiceNumbers ? implode(', ', $invoiceNumbers).' (Count: '.count($invoiceNumbers).')' : 'NONE')."\n";
    echo "  >> HEALTH & VALIDATION:\n      {$health}\n\n";
}

// -----------------------------------------------------------------------------------------
// SECTION 4: UNLINKED / ORPHANED RECEIPTS
// -----------------------------------------------------------------------------------------
echo "-----------------------------------------------------------------------------------------\n";
echo "4. ORPHANED / UNLINKED RECEIPTS (Not attached to any trip)\n";
echo "-----------------------------------------------------------------------------------------\n";

$linkedInvoiceIds = DB::table('trip_receipts')->pluck('invoice_id')->all();

$unlinkedLr = DB::table('invoices')
    ->where('template_name', 'lr_receipt')
    ->whereNotIn('id', $linkedInvoiceIds)
    ->pluck('invoice_number')
    ->all();

$unlinkedLorry = DB::table('invoices')
    ->where('template_name', 'lorry_receipt')
    ->whereNotIn('id', $linkedInvoiceIds)
    ->pluck('invoice_number')
    ->all();

$unlinkedInvoice = DB::table('invoices')
    ->where('template_name', 'invoice_receipt')
    ->whereNotIn('id', $linkedInvoiceIds)
    ->pluck('invoice_number')
    ->all();

echo 'Unlinked LR Receipts:      '.($unlinkedLr ? implode(', ', $unlinkedLr) : 'None (All linked)')."\n";
echo 'Unlinked Lorry Receipts:   '.($unlinkedLorry ? implode(', ', $unlinkedLorry) : 'None (All linked)')."\n";
echo 'Unlinked Invoice Receipts: '.($unlinkedInvoice ? implode(', ', $unlinkedInvoice) : 'None (All linked)')."\n";
echo "=========================================================================================\n";
