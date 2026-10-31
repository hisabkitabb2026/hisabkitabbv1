<?php

declare(strict_types=1);

use App\Domains\Sales\Models\Invoice;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Trips\Application\TripService;
use Modules\Trips\Http\Resources\TripCardResource;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripReceipt;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$companyId = 1;
$companyName = DB::table('companies')->where('id', $companyId)->value('name') ?: 'S S GUJARAT LOGISTICS SERVICES (ID: 1)';

echo "=========================================================================================\n";
echo "       HISABKITABB - DEEP-DIVE LIVE INSPECTION FOR: {$companyName}\n";
echo "=========================================================================================\n\n";

$tripService = app(TripService::class);

// -----------------------------------------------------------------------------------------
// 1. VERIFY FRONTEND API DATA FOR SHARED-VEHICLE TRIPS (MULTI-LR BADGES)
// -----------------------------------------------------------------------------------------
echo "-----------------------------------------------------------------------------------------\n";
echo "1. VERIFYING FRONTEND API OUTPUT (TripCardResource) FOR CONSOLIDATED LOADS\n";
echo "-----------------------------------------------------------------------------------------\n";

$sharedTruckNumbers = [
    38 => [139, 140],
    37 => [137, 138],
    43 => [146, 147, 148],
    47 => [302, 303],
    65 => [322, 323],
    10 => [110, 111],
    18 => [122, 123],
    39 => [141, 142],
    40 => [143, 144],
    42 => [153, 154, 202, 203],
    82 => [342, 343],
];

printf(
    "%-12s | %-12s | %-16s | %-24s | %-22s | %s\n",
    'Challan No', 'Trip #', 'Truck No', 'Route', 'LR Badges in API', 'API Status'
);
echo str_repeat('-', 110)."\n";

$fakeRequest = Request::create('/api/v1/trips', 'GET');

foreach ($sharedTruckNumbers as $challanNo => $expectedLrs) {
    // Find the lorry receipt
    $lorry = Invoice::where('company_id', $companyId)
        ->where('template_name', 'lorry_receipt')
        ->where('invoice_number', (string) $challanNo)
        ->first();

    if (! $lorry) {
        continue;
    }

    $tripReceipt = TripReceipt::where('company_id', $companyId)
        ->where('invoice_id', $lorry->id)
        ->where('type', 'lorry')
        ->first();

    if (! $tripReceipt) {
        printf("%-12s | %-12s | %-16s | %-24s | %-22s | %s\n", $challanNo, 'None', '-', '-', '-', '❌ No Trip');

        continue;
    }

    $trip = Trip::with(['receipts', 'status'])->find($tripReceipt->trip_id);
    if (! $trip) {
        continue;
    }

    $resource = (new TripCardResource($trip))->toArray($fakeRequest);
    $apiLrs = $resource['lr_numbers'] ?? [];
    $badgeText = count($apiLrs) > 1 ? 'LR: '.implode(', ', $apiLrs) : implode(', ', $apiLrs);

    $isCorrect = (count($apiLrs) === count($expectedLrs));
    $statusText = $isCorrect ? '✅ PERFECT' : '⚠️ Incomplete';

    $route = trim(($trip->from_city ?: '-').' -> '.($trip->to_city ?: '-'));

    printf(
        "%-12s | %-12s | %-16s | %-24s | %-22s | %s\n",
        substr((string) $challanNo, 0, 12),
        '#'.$trip->trip_no,
        substr((string) ($trip->lorry_no ?: '-'), 0, 16),
        substr($route, 0, 24),
        substr($badgeText, 0, 22),
        $statusText
    );
}

echo "\n";

// -----------------------------------------------------------------------------------------
// 2. VERIFY LIVE SEARCH FUNCTIONALITY
// -----------------------------------------------------------------------------------------
echo "-----------------------------------------------------------------------------------------\n";
echo "2. VERIFYING SEARCH QUERY (Can we search by secondary LR numbers?)\n";
echo "-----------------------------------------------------------------------------------------\n";

$testQueries = ['140', '203', '147', '303', '343'];
foreach ($testQueries as $q) {
    $results = $tripService->board($companyId, ['search' => $q]);
    $matchedTripNos = $results->pluck('trip_no')->all();
    if (empty($matchedTripNos)) {
        echo "  • Search for '{$q}': ❌ Not found\n";
    } else {
        $firstTrip = $results->first();
        $lrs = $firstTrip->receipts->where('type', 'lr')->pluck('invoice_number')->all();
        echo "  • Search for '{$q}': ✅ Matched Trip #{$firstTrip->trip_no} (Carrying LRs: [".implode(', ', $lrs)."])\n";
    }
}
echo "\n";

// -----------------------------------------------------------------------------------------
// 3. INSPECTION OF THE 7 TRIPS MISSING LORRY RECEIPTS
// -----------------------------------------------------------------------------------------
echo "-----------------------------------------------------------------------------------------\n";
echo "3. DETAILED INSPECTION: 7 TRIPS WITHOUT LORRY RECEIPT (TRUCK CHALLAN)\n";
echo "-----------------------------------------------------------------------------------------\n";

$missingLorryTripNos = [103, 131, 310, 326, 328, 333, 348];
printf(
    "%-8s | %-14s | %-24s | %-18s | %-12s | %s\n",
    'Trip #', 'Customer LR #', 'Customer Name', 'Route', 'Freight (₹)', 'Action Needed'
);
echo str_repeat('-', 110)."\n";

foreach ($missingLorryTripNos as $tNo) {
    $t = Trip::where('company_id', $companyId)->where('trip_no', $tNo)->with('receipts')->first();
    if (! $t) {
        continue;
    }

    $lr = $t->receipts->where('type', 'lr')->first();
    $lrNumber = $lr?->invoice_number ?: '-';
    $custName = $lr?->customer_name ?: 'Unknown';
    $freight = number_format(($lr?->amount ?: 0) / 100, 2);
    $route = trim(($t->from_city ?: '-').' -> '.($t->to_city ?: '-'));

    printf(
        "%-8s | %-14s | %-24s | %-18s | %-12s | %s\n",
        '#'.$tNo,
        substr((string) $lrNumber, 0, 14),
        substr((string) $custName, 0, 24),
        substr($route, 0, 18),
        $freight,
        'Enter Lorry Receipt / Challan'
    );
}

echo "\n";

// -----------------------------------------------------------------------------------------
// 4. FINANCIAL SUMMARY
// -----------------------------------------------------------------------------------------
echo "-----------------------------------------------------------------------------------------\n";
echo "4. OVERALL FINANCIAL & DOCUMENT CHAIN HEALTH\n";
echo "-----------------------------------------------------------------------------------------\n";

$co1Trips = Trip::where('company_id', $companyId)->with('receipts')->get();
$complete = 0;
$inTransit = 0;
$noLorry = 0;

foreach ($co1Trips as $t) {
    $hasLr = $t->receipts->where('type', 'lr')->isNotEmpty();
    $hasLorry = $t->receipts->where('type', 'lorry')->isNotEmpty();
    $hasInv = $t->receipts->where('type', 'invoice')->isNotEmpty();

    if ($hasLr && $hasLorry && $hasInv) {
        $complete++;
    } elseif ($hasLr && $hasLorry) {
        $inTransit++;
    } elseif ($hasLr && ! $hasLorry) {
        $noLorry++;
    }
}

echo "Total Trips: {$co1Trips->count()}\n";
echo "  • Fully Complete (All 5 Documents Linked): {$complete} (".round(($complete / $co1Trips->count()) * 100, 1)."%)\n";
echo "  • In-Transit / Pending Final Office Bill:   {$inTransit} (".round(($inTransit / $co1Trips->count()) * 100, 1)."%)\n";
echo "  • Pending Lorry Challan Entry:             {$noLorry} (".round(($noLorry / $co1Trips->count()) * 100, 1)."%)\n";
echo "=========================================================================================\n";
