<?php

declare(strict_types=1);

use App\Domains\Sales\Models\Invoice;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripReceipt;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$companyId = 1;
$fromDate = Carbon::now()->subYear()->startOfDay();
$toDate = Carbon::now()->endOfDay();

echo "=========================================================================================\n";
echo "    AUDIT: MISSING LORRY RECEIPTS & INVOICE RECEIPTS PER TRIP (COMPANY 1)\n";
echo "    Date Range: {$fromDate->toDateString()} to {$toDate->toDateString()}\n";
echo "=========================================================================================\n\n";

$trips = Trip::query()
    ->where('company_id', $companyId)
    ->whereNull('cancelled_at')
    ->where(function ($q) use ($fromDate, $toDate): void {
        $q->where(function ($sq) use ($fromDate, $toDate): void {
            $sq->whereNotNull('pickup_date')
                ->where('pickup_date', '>=', $fromDate)
                ->where('pickup_date', '<=', $toDate);
        })->orWhere(function ($sq) use ($fromDate, $toDate): void {
            $sq->whereNull('pickup_date')
                ->where('created_at', '>=', $fromDate)
                ->where('created_at', '<=', $toDate);
        });
    })
    ->with(['receipts'])
    ->orderByRaw('CAST(trip_no AS UNSIGNED) ASC, trip_no ASC')
    ->get();

$totalTrips = $trips->count();

$fullyLinked = [];
$missingLorryOnly = [];
$missingInvoiceOnly = [];
$missingBoth = [];

foreach ($trips as $trip) {
    $lrReceipts = $trip->receipts->where('type', TripReceipt::TYPE_LR);
    $lorryReceipts = $trip->receipts->where('type', TripReceipt::TYPE_LORRY);
    $invoiceReceipts = $trip->receipts->where('type', TripReceipt::TYPE_INVOICE);

    $lrNumbers = $lrReceipts->map(fn ($r) => (string) $r->invoice_number)->filter()->implode(', ') ?: '—';
    $customerNames = $lrReceipts->pluck('customer_name')->filter()->unique()->implode(', ') ?: '—';
    $lorryNumbers = $lorryReceipts->map(fn ($r) => (string) $r->invoice_number)->filter()->implode(', ') ?: '—';
    $invoiceNumbers = $invoiceReceipts->map(fn ($r) => (string) $r->invoice_number)->filter()->implode(', ') ?: '—';

    $hasLorry = $lorryReceipts->isNotEmpty();
    $hasInvoice = $invoiceReceipts->isNotEmpty();

    $item = [
        'trip_no' => $trip->trip_no,
        'lr_numbers' => $lrNumbers,
        'customer' => $customerNames,
        'route' => trim(($trip->from_city ?? '—').' → '.($trip->to_city ?? '—')),
        'truck_no' => $trip->lorry_no ?? '—',
        'lorry_no' => $lorryNumbers,
        'invoice_no' => $invoiceNumbers,
        'has_lorry' => $hasLorry,
        'has_invoice' => $hasInvoice,
    ];

    if ($hasLorry && $hasInvoice) {
        $fullyLinked[] = $item;
    } elseif (! $hasLorry && ! $hasInvoice) {
        $missingBoth[] = $item;
    } elseif (! $hasLorry) {
        $missingLorryOnly[] = $item;
    } else {
        $missingInvoiceOnly[] = $item;
    }
}

$totalMissingLorry = count($missingLorryOnly) + count($missingBoth);
$totalMissingInvoice = count($missingInvoiceOnly) + count($missingBoth);

echo "SUMMARY BREAKDOWN:\n";
echo "  • Total Active Trips in 1-Year Range:          {$totalTrips}\n";
echo '  • Fully Complete Trips (Has LR + Lorry + Inv): '.count($fullyLinked).' ('.round((count($fullyLinked) / max(1, $totalTrips)) * 100, 1)."%)\n\n";

echo "DOCUMENT GAPS:\n";
echo "  • Trips Missing Lorry Receipt (Truck Challan): {$totalMissingLorry} / {$totalTrips} (has LR, but no Lorry Receipt linked)\n";
echo "  • Trips Missing Invoice Receipt (Bill/Tax Inv):{$totalMissingInvoice} / {$totalTrips} (has LR, but no Invoice Receipt linked)\n";
echo '  • Trips Missing BOTH Lorry & Invoice:          '.count($missingBoth)." / {$totalTrips}\n";
echo "-----------------------------------------------------------------------------------------\n\n";

// 1. TRIPS MISSING LORRY RECEIPTS
if ($totalMissingLorry > 0) {
    echo "=========================================================================================\n";
    echo "1. TRIPS MISSING LORRY RECEIPT ({$totalMissingLorry} TRIPS)\n";
    echo "=========================================================================================\n";
    printf("%-10s | %-16s | %-28s | %-16s | %-20s\n", 'TRIP #', 'LR NO(S)', 'CUSTOMER', 'TRUCK NO', 'ROUTE');
    echo str_repeat('-', 100)."\n";
    $lorryMissingAll = array_merge($missingLorryOnly, $missingBoth);
    usort($lorryMissingAll, fn ($a, $b) => ((int) $a['trip_no']) <=> ((int) $b['trip_no']));
    foreach ($lorryMissingAll as $row) {
        printf("%-10s | %-16s | %-28s | %-16s | %-20s\n",
            '#'.$row['trip_no'],
            $row['lr_numbers'],
            mb_substr($row['customer'], 0, 28),
            $row['truck_no'],
            mb_substr($row['route'], 0, 20)
        );
    }
    echo str_repeat('-', 100)."\n\n";
}

// 2. TRIPS MISSING INVOICE RECEIPTS
if ($totalMissingInvoice > 0) {
    echo "=========================================================================================\n";
    echo "2. TRIPS MISSING INVOICE RECEIPT ({$totalMissingInvoice} TRIPS)\n";
    echo "=========================================================================================\n";
    printf("%-10s | %-16s | %-28s | %-16s | %-20s\n", 'TRIP #', 'LR NO(S)', 'CUSTOMER', 'TRUCK NO', 'ROUTE');
    echo str_repeat('-', 100)."\n";
    $invoiceMissingAll = array_merge($missingInvoiceOnly, $missingBoth);
    usort($invoiceMissingAll, fn ($a, $b) => ((int) $a['trip_no']) <=> ((int) $b['trip_no']));
    foreach ($invoiceMissingAll as $row) {
        printf("%-10s | %-16s | %-28s | %-16s | %-20s\n",
            '#'.$row['trip_no'],
            $row['lr_numbers'],
            mb_substr($row['customer'], 0, 28),
            $row['truck_no'],
            mb_substr($row['route'], 0, 20)
        );
    }
    echo str_repeat('-', 100)."\n\n";
}

// 3. FULLY COMPLETE TRIPS
echo "=========================================================================================\n";
echo '3. FULLY COMPLETE TRIPS ('.count($fullyLinked)." TRIPS - HAS LR + LORRY + INVOICE)\n";
echo "=========================================================================================\n";
printf("%-10s | %-14s | %-14s | %-14s | %-24s\n", 'TRIP #', 'LR NO(S)', 'LORRY NO', 'INVOICE NO', 'CUSTOMER');
echo str_repeat('-', 85)."\n";
foreach ($fullyLinked as $row) {
    printf("%-10s | %-14s | %-14s | %-14s | %-24s\n",
        '#'.$row['trip_no'],
        $row['lr_numbers'],
        $row['lorry_no'],
        $row['invoice_no'],
        mb_substr($row['customer'], 0, 24)
    );
}
echo str_repeat('-', 85)."\n";
