<?php

declare(strict_types=1);

use App\Domains\Sales\Models\Invoice;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Modules\Trips\Application\TripReportService;
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
echo "       AUDIT: LR RECEIPTS VS 1-YEAR TRIP REPORT FOR COMPANY 1 (VAPI)\n";
echo "       Date Range: {$fromDate->toDateString()} to {$toDate->toDateString()}\n";
echo "=========================================================================================\n\n";

// 1. Fetch 1-Year Trip Report rows through the actual service used by the page
$reportService = app(TripReportService::class);
$reportTrips = $reportService->tripDetails($companyId, $fromDate->toDateString(), $toDate->toDateString());

$reportLrMap = []; // lr_number => trip_no
$reportTripNumbers = [];
foreach ($reportTrips as $row) {
    $reportTripNumbers[] = $row['trip_no'];
    $lrs = array_map('trim', explode(',', (string) ($row['lr_numbers'] ?? '')));
    foreach ($lrs as $lr) {
        if ($lr !== '' && $lr !== '—') {
            $reportLrMap[$lr] = $row['trip_no'];
        }
    }
}

// 2. Fetch all LR Receipts in Company 1
$allLrs = Invoice::query()
    ->where('company_id', $companyId)
    ->whereIn('template_name', ['lr_receipt', 'lr'])
    ->where(function ($q) use ($fromDate, $toDate) {
        $q->whereBetween('invoice_date', [$fromDate, $toDate])
            ->orWhereBetween('created_at', [$fromDate, $toDate]);
    })
    ->with('customer')
    ->orderByRaw('CAST(invoice_number AS UNSIGNED) ASC, invoice_number ASC')
    ->get();

$allTimeCount = Invoice::query()
    ->where('company_id', $companyId)
    ->whereIn('template_name', ['lr_receipt', 'lr'])
    ->count();

$linkedReceipts = TripReceipt::query()
    ->where('company_id', $companyId)
    ->where('type', TripReceipt::TYPE_LR)
    ->with('trip')
    ->get()
    ->keyBy('invoice_id');

$appearing = [];
$outsideRange = [];
$cancelled = [];
$unlinked = [];

foreach ($allLrs as $lr) {
    $num = (string) $lr->invoice_number;
    $receipt = $linkedReceipts->get($lr->id);

    if (! $receipt || ! $receipt->trip) {
        $unlinked[] = [
            'lr_no' => $num,
            'customer' => $lr->customer?->name ?? '—',
            'date' => $lr->invoice_date?->format('d M Y') ?? '—',
            'amount' => $lr->total,
            'trip_no' => 'None',
            'status' => 'UNLINKED (No Trip)',
        ];

        continue;
    }

    $trip = $receipt->trip;

    if ($trip->cancelled_at !== null) {
        $cancelled[] = [
            'lr_no' => $num,
            'customer' => $lr->customer?->name ?? '—',
            'date' => $lr->invoice_date?->format('d M Y') ?? '—',
            'amount' => $lr->total,
            'trip_no' => '#'.$trip->trip_no,
            'status' => 'CANCELLED TRIP',
        ];

        continue;
    }

    // Check if it appears in report
    if (isset($reportLrMap[$num])) {
        $appearing[] = [
            'lr_no' => $num,
            'customer' => $lr->customer?->name ?? '—',
            'date' => $lr->invoice_date?->format('d M Y') ?? '—',
            'amount' => $lr->total,
            'trip_no' => '#'.$trip->trip_no,
            'status' => 'IN REPORT',
        ];
    } else {
        $tripDate = $trip->pickup_date?->format('d M Y') ?? ($trip->created_at?->format('d M Y') ?? '—');
        $outsideRange[] = [
            'lr_no' => $num,
            'customer' => $lr->customer?->name ?? '—',
            'date' => $lr->invoice_date?->format('d M Y') ?? '—',
            'amount' => $lr->total,
            'trip_no' => '#'.$trip->trip_no,
            'status' => "TRIP DATE OUTSIDE RANGE ({$tripDate})",
        ];
    }
}

// -----------------------------------------------------------------------------------------
// SUMMARY METRICS
// -----------------------------------------------------------------------------------------
echo "METRICS SUMMARY:\n";
echo "  • Total LR Receipts in DB (All-Time):          {$allTimeCount}\n";
echo "  • Total LR Receipts in DB (Last 1 Year):       {$allLrs->count()}\n";
echo '  • Total Trips in 1-Year Trip Report:           '.count($reportTrips)."\n\n";

echo "RECONCILIATION BREAKDOWN (Last 1 Year LR Receipts):\n";
echo '  [+] In 1-Year Trip Report:                     '.count($appearing)."  (Successfully showing in report)\n";
echo '  [-] Linked to Trips Outside Date Range:        '.count($outsideRange)."\n";
echo '  [-] Linked to Cancelled Trips:                 '.count($cancelled)."\n";
echo '  [-] Unlinked LRs (Not assigned to any Trip):   '.count($unlinked)."\n";
echo "-----------------------------------------------------------------------------------------\n\n";

// Display Table
printf("%-12s | %-12s | %-32s | %-12s | %-10s | %-20s\n", 'LR NO', 'LR DATE', 'CUSTOMER', 'AMOUNT', 'TRIP #', 'REPORT STATUS');
echo str_repeat('-', 110)."\n";

$allRows = array_merge($appearing, $outsideRange, $cancelled, $unlinked);
usort($allRows, function ($a, $b) {
    return ((int) $a['lr_no']) <=> ((int) $b['lr_no']);
});

foreach ($allRows as $r) {
    $formattedAmt = 'INR '.number_format($r['amount'] / 100, 2);
    $statusIcon = str_contains($r['status'], 'IN REPORT') ? 'YES' : 'NO ('.$r['status'].')';
    printf("%-12s | %-12s | %-32s | %-12s | %-10s | %-20s\n",
        $r['lr_no'],
        $r['date'],
        mb_substr($r['customer'], 0, 32),
        $formattedAmt,
        $r['trip_no'],
        $statusIcon
    );
}

echo str_repeat('-', 110)."\n\n";

if (count($outsideRange) === 0 && count($cancelled) === 0 && count($unlinked) === 0) {
    echo "EXCELLENT: 100% of LR Receipts in Company 1 are present in the 1-Year Trip Report!\n";
} else {
    echo "NOTE: LRs marked 'NO' are either unlinked to a trip or their trip falls outside the 1-year window.\n";
}
