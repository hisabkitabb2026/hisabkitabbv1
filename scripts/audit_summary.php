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
echo "              HISABKITABB - TRIPS HEALTH SUMMARY & UNLINKED AUDIT                        \n";
echo "=========================================================================================\n\n";

$trips = Trip::with(['status', 'receipts'])->orderBy('trip_no')->get();

$categories = [
    'healthy_delivered' => [], // Both LR, Lorry, and Invoice linked
    'healthy_in_transit' => [], // Both LR and Lorry linked, invoice pending
    'multiple_lorry' => [], // Multiple Lorry Receipts linked
    'missing_lorry' => [], // Has LR, but NO Lorry Receipt
    'standalone_lorry' => [], // Has Lorry Receipt, but NO LR
    'bilty_mismatch' => [], // Lorry Receipt bilty doesn't match LR
    'empty_trip' => [], // Neither LR nor Lorry
];

foreach ($trips as $trip) {
    $lrReceipts = $trip->receipts->where('type', 'lr');
    $lorryReceipts = $trip->receipts->where('type', 'lorry');
    $invoiceReceipts = $trip->receipts->where('type', 'invoice');

    $lrNumbers = $lrReceipts->pluck('invoice_number')->filter()->values()->all();
    $lorryNumbers = $lorryReceipts->pluck('invoice_number')->filter()->values()->all();

    $lorryInvoiceIds = $lorryReceipts->pluck('invoice_id')->all();
    $lorryInvoices = DB::table('invoices')->whereIn('id', $lorryInvoiceIds)->get();

    $hasEmptyBilties = false;
    $hasMismatch = false;

    foreach ($lorryInvoices as $li) {
        $b = trim((string) $li->tr_received_no_bilties);
        if ($b === '') {
            $hasEmptyBilties = true;
        } else {
            $tokens = preg_split('/[\s,;]+/u', $b, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $tokens = array_map('trim', $tokens);
            if (empty(array_intersect($tokens, $lrNumbers)) && ! empty($lrNumbers)) {
                $hasMismatch = true;
            }
        }
    }

    if ($lrReceipts->isEmpty() && $lorryReceipts->isEmpty()) {
        $categories['empty_trip'][] = $trip->trip_no;
    } elseif ($lrReceipts->isNotEmpty() && $lorryReceipts->isEmpty()) {
        $categories['missing_lorry'][] = $trip->trip_no;
    } elseif ($lrReceipts->isEmpty() && $lorryReceipts->isNotEmpty()) {
        $categories['standalone_lorry'][] = $trip->trip_no;
    } elseif ($hasMismatch) {
        $categories['bilty_mismatch'][] = $trip->trip_no;
    } elseif ($lorryReceipts->count() > 1) {
        $categories['multiple_lorry'][] = $trip->trip_no;
    } elseif ($invoiceReceipts->isNotEmpty()) {
        $categories['healthy_delivered'][] = $trip->trip_no;
    } else {
        $categories['healthy_in_transit'][] = $trip->trip_no;
    }
}

echo "TOTAL TRIPS ON BOARD: {$trips->count()}\n";
echo "-----------------------------------------------------------------------------------------\n";
printf("%-45s | %-8s | %s\n", 'Category', 'Count', 'Sample Trip #s');
echo str_repeat('-', 100)."\n";

printf(
    "%-45s | %-8d | %s\n",
    '✅ 100% Complete & Delivered (LR + Lorry + Inv)',
    count($categories['healthy_delivered']),
    implode(', ', array_slice($categories['healthy_delivered'], 0, 10)).'...'
);

printf(
    "%-45s | %-8d | %s\n",
    '🚚 In-Transit (LR + Lorry matched, Inv pending)',
    count($categories['healthy_in_transit']),
    implode(', ', array_slice($categories['healthy_in_transit'], 0, 10)).'...'
);

printf(
    "%-45s | %-8d | %s\n",
    'ℹ️ Multi-Lorry / Grouped Consignments',
    count($categories['multiple_lorry']),
    implode(', ', $categories['multiple_lorry'])
);

printf(
    "%-45s | %-8d | %s\n",
    '⚠️ Missing Lorry Receipt (LR only, no truck)',
    count($categories['missing_lorry']),
    implode(', ', array_slice($categories['missing_lorry'], 0, 10)).'...'
);

printf(
    "%-45s | %-8d | %s\n",
    '⚠️ Standalone Lorry (Lorry only, no LR invoice)',
    count($categories['standalone_lorry']),
    implode(', ', array_slice($categories['standalone_lorry'], 0, 15)).'...'
);

printf(
    "%-45s | %-8d | %s\n",
    '❌ Bilty Mismatches',
    count($categories['bilty_mismatch']),
    implode(', ', $categories['bilty_mismatch'])
);

printf(
    "%-45s | %-8d | %s\n",
    '⚠️ Empty Trips (No receipts)',
    count($categories['empty_trip']),
    implode(', ', $categories['empty_trip'])
);

echo "\n-----------------------------------------------------------------------------------------\n";
echo "UNLINKED / ORPHANED RECEIPTS SUMMARY\n";
echo "-----------------------------------------------------------------------------------------\n";
$linkedIds = DB::table('trip_receipts')->pluck('invoice_id')->all();

$unlinkedLr = DB::table('invoices')->where('template_name', 'lr_receipt')->whereNotIn('id', $linkedIds)->pluck('invoice_number')->all();
$unlinkedLorry = DB::table('invoices')->where('template_name', 'lorry_receipt')->whereNotIn('id', $linkedIds)->pluck('invoice_number')->all();
$unlinkedInvoice = DB::table('invoices')->where('template_name', 'invoice_receipt')->whereNotIn('id', $linkedIds)->pluck('invoice_number')->all();

echo 'Unlinked LR Receipts:      '.count($unlinkedLr).' -> '.($unlinkedLr ? implode(', ', array_slice($unlinkedLr, 0, 20)) : 'None (All linked)')."\n";
echo 'Unlinked Lorry Receipts:   '.count($unlinkedLorry).' -> '.($unlinkedLorry ? implode(', ', $unlinkedLorry) : 'None (All linked)')."\n";
echo 'Unlinked Invoice Receipts: '.count($unlinkedInvoice).' -> '.($unlinkedInvoice ? implode(', ', array_slice($unlinkedInvoice, 0, 20)) : 'None (All linked)')."\n";
echo "=========================================================================================\n";
