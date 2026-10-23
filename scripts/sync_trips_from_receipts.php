<?php

declare(strict_types=1);

use App\Domains\Accounts\Models\Company;
use App\Domains\Receivables\Models\Payment;
use App\Domains\Receivables\Models\PaymentAllocation;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Contracts\Console\Kernel;
use Modules\Trips\Application\TripLifecycleService;
use Modules\Trips\Application\TripService;
use Modules\Trips\Application\TripStatusService;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripReceipt;
use Modules\Trips\Models\TripStatus;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$isExecute = in_array('--execute', $argv, true);
$isFresh = in_array('--fresh', $argv, true);

echo "============================================================\n";
echo "  TRIP & TRANSPORT RECEIPTS HISTORICAL SYNC SCRIPT\n";
echo "  Mode: ".($isExecute ? 'EXECUTE (Applying changes to DB)' : 'DRY RUN (Preview only, use --execute to apply)')."\n";
if ($isFresh) {
    echo "  Option: FRESH (Wipe existing trip records first)\n";
}
echo "============================================================\n\n";

if ($isFresh) {
    echo "Clearing all existing trip records...\n";
    if ($isExecute) {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \Illuminate\Support\Facades\DB::table('trip_receipts')->truncate();
        \Illuminate\Support\Facades\DB::table('trip_events')->truncate();
        \Illuminate\Support\Facades\DB::table('trip_expenses')->truncate();
        \Illuminate\Support\Facades\DB::table('trips')->truncate();
        \Illuminate\Support\Facades\DB::table('media')->where('model_type', 'trip')->delete();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        echo "[✓] All Trips module records deleted successfully.\n\n";
    } else {
        echo "[Preview] Would wipe tables: trips, trip_receipts, trip_events, trip_expenses, and trip documents.\n\n";
    }
}

$companies = Company::all();

foreach ($companies as $company) {
    $companyId = (int) $company->id;
    echo "--- Processing Company #{$companyId}: {$company->name} ---\n";

    /** @var TripStatusService $statusService */
    $statusService = app(TripStatusService::class);
    /** @var TripLifecycleService $lifecycleService */
    $lifecycleService = app(TripLifecycleService::class);
    /** @var TripService $tripService */
    $tripService = app(TripService::class);

    if ($isExecute) {
        $statusService->ensureDefaults($companyId);
    }

    // 1. Scan LR Receipts
    $lrInvoices = Invoice::query()
        ->where('company_id', $companyId)
        ->where('template_name', 'lr_receipt')
        ->orderBy('id')
        ->get();

    echo "Found {$lrInvoices->count()} LR Receipts.\n";

    if ($isExecute) {
        foreach ($lrInvoices as $lr) {
            $trip = $lifecycleService->handleLrReceipt($lr);
            echo "  [+] LR #{$lr->invoice_number} => Trip #{$trip?->trip_no} (Status: {$trip?->status?->name})\n";
        }
    }

    // 2. Scan Lorry Receipts
    $lorryInvoices = Invoice::query()
        ->where('company_id', $companyId)
        ->where('template_name', 'lorry_receipt')
        ->orderBy('id')
        ->get();

    echo "\nFound {$lorryInvoices->count()} Lorry Receipts.\n";

    if ($isExecute) {
        foreach ($lorryInvoices as $lorry) {
            $bilties = $lorry->tr_received_no_bilties ?? 'none';
            $trip = $lifecycleService->handleLorryReceipt($lorry);
            echo "  [+] Lorry #{$lorry->invoice_number} (Bilties: '{$bilties}') => Trip #{$trip?->trip_no} (Status: {$trip?->status?->name})\n";
        }
    }

    // 3. Scan Invoice Receipts
    $officeInvoices = Invoice::query()
        ->where('company_id', $companyId)
        ->where('template_name', 'invoice_receipt')
        ->with('items')
        ->orderBy('id')
        ->get();

    echo "\nFound {$officeInvoices->count()} Invoice Receipts.\n";

    if ($isExecute) {
        foreach ($officeInvoices as $inv) {
            $consignments = $lifecycleService->extractConsignmentNumbers($inv);
            $conStr = ! empty($consignments) ? implode(', ', $consignments) : 'none';
            $trip = $lifecycleService->handleInvoiceReceipt($inv);
            echo "  [+] Invoice #{$inv->invoice_number} (Consignments: [{$conStr}]) => Trip #{$trip?->trip_no} (Status: {$trip?->status?->name})\n";
        }
    }

    // 4. Scan Payments
    $payments = Payment::query()
        ->where('company_id', $companyId)
        ->with('allocations.invoice')
        ->orderBy('id')
        ->get();

    echo "\nFound {$payments->count()} Customer Payments.\n";

    if ($isExecute) {
        foreach ($payments as $payment) {
            $lifecycleService->syncFromPayment($payment);
        }
    }

    // 5. Summary Statistics for Company
    if ($isExecute) {
        $trips = Trip::query()->where('company_id', $companyId)->with(['receipts', 'status'])->get();
        echo "\n=== SUMMARY FOR COMPANY #{$companyId} ===\n";
        echo "Total Trips: {$trips->count()}\n";

        $byStatus = $trips->groupBy('status.name');
        foreach ($byStatus as $statusName => $group) {
            echo "  - {$statusName}: {$group->count()} trips\n";
        }

        $paidCount = 0;
        $partialCount = 0;
        $unpaidCount = 0;

        foreach ($trips as $t) {
            $m = $tripService->money($t);
            match ($m['payment_status']) {
                'PAID' => $paidCount++,
                'PARTIALLY_PAID' => $partialCount++,
                default => $unpaidCount++,
            };
        }

        echo "Payment Settlement Breakdown:\n";
        echo "  - Fully Paid: {$paidCount} trips\n";
        echo "  - Partially Paid: {$partialCount} trips\n";
        echo "  - Unpaid: {$unpaidCount} trips\n";
    }

    echo "\n";
}

echo "=== SYNC OPERATION COMPLETED ===\n";
