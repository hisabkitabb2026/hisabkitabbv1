<?php

declare(strict_types=1);

use App\Domains\Sales\Models\Invoice;
use Illuminate\Contracts\Console\Kernel;
use Modules\Trips\Application\TripService;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripReceipt;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$companyId = 1;
$tripService = app(TripService::class);

$missingLorryTrips = [103, 131, 310, 326, 328, 333, 348];
$missingInvoiceTrips = [157, 158, 159, 321, 337, 339, 340, 341, 345, 346, 347, 348];

echo "=========================================================================================\n";
echo "      DIAGNOSIS: CANDIDATE DOCUMENTS IN DATABASE FOR TRIPS MISSING RECEIPTS\n";
echo "=========================================================================================\n\n";

// 1. Check for Missing Lorry Receipts candidates using the system's exact matching logic
echo "1. CHECKING FOR UNLINKED LORRY RECEIPTS (CHALLANS):\n";
echo str_repeat('-', 90)."\n";

foreach ($missingLorryTrips as $tripNo) {
    $trip = Trip::query()->where('company_id', $companyId)->where('trip_no', $tripNo)->with('receipts')->first();
    if (! $trip) {
        continue;
    }
    $lrNos = $trip->receipts->where('type', 'lr')->pluck('invoice_number')->filter()->all();

    $candidates = $tripService->matchLorryReceipts($companyId, $lrNos);

    if ($candidates->isNotEmpty()) {
        $cList = $candidates->map(fn ($c) => "Lorry Receipt #{$c->invoice_number} (Truck: {$c->tr_lorry_no}, Bilties: {$c->tr_received_no_bilties})")->implode('; ');
        echo "Trip #{$tripNo} (LR: ".implode(', ', $lrNos)."): [MATCH FOUND] -> {$cList}\n";
    } else {
        // Also check if any Lorry Receipt has matching truck number
        $truckMatches = Invoice::query()
            ->where('company_id', $companyId)
            ->where('template_name', 'lorry_receipt')
            ->where('tr_lorry_no', 'LIKE', '%'.str_replace(' ', '', (string) $trip->lorry_no).'%')
            ->get(['id', 'invoice_number', 'tr_lorry_no', 'tr_received_no_bilties']);

        if ($truckMatches->isNotEmpty()) {
            $tList = $truckMatches->map(fn ($c) => "#{$c->invoice_number} (Bilties on challan: {$c->tr_received_no_bilties})")->implode('; ');
            echo "Trip #{$tripNo} (LR: ".implode(', ', $lrNos)."): No bilty match, but found challan with truck {$trip->lorry_no} -> {$tList}\n";
        } else {
            echo "Trip #{$tripNo} (LR: ".implode(', ', $lrNos)."): No candidate Lorry Receipt found in DB (Challan has not been created yet)\n";
        }
    }
}

echo "\n".str_repeat('=', 90)."\n\n";

// 2. Check for Missing Invoice Receipts candidates using the system's exact matching logic
echo "2. CHECKING FOR UNLINKED INVOICE RECEIPTS (CUSTOMER INVOICES):\n";
echo str_repeat('-', 90)."\n";

$alreadyLinkedInvoiceIds = TripReceipt::query()
    ->where('company_id', $companyId)
    ->where('type', TripReceipt::TYPE_INVOICE)
    ->pluck('invoice_id')
    ->all();

foreach ($missingInvoiceTrips as $tripNo) {
    $trip = Trip::query()->where('company_id', $companyId)->where('trip_no', $tripNo)->with('receipts')->first();
    if (! $trip) {
        continue;
    }
    $lrNos = $trip->receipts->where('type', 'lr')->pluck('invoice_number')->filter()->all();

    $candidates = Invoice::query()
        ->where('company_id', $companyId)
        ->where('template_name', 'invoice_receipt')
        ->when(! empty($alreadyLinkedInvoiceIds), fn ($q) => $q->whereNotIn('id', $alreadyLinkedInvoiceIds))
        ->whereHas('items', function ($q) use ($lrNos) {
            $q->whereIn('name', $lrNos)
                ->orWhereIn('tr_consignment_number', $lrNos);
        })
        ->with('customer')
        ->get(['id', 'invoice_number', 'invoice_date', 'customer_id']);

    if ($candidates->isNotEmpty()) {
        $cList = $candidates->map(fn ($c) => "Invoice #{$c->invoice_number}")->implode(', ');
        echo "Trip #{$tripNo} (LR: ".implode(', ', $lrNos)."): [MATCH FOUND] -> {$cList}\n";
    } else {
        echo "Trip #{$tripNo} (LR: ".implode(', ', $lrNos)."): No candidate Invoice found in DB (Customer has not been billed yet)\n";
    }
}

echo str_repeat('=', 90)."\n";
