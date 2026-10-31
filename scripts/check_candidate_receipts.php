<?php

declare(strict_types=1);

use App\Domains\Sales\Models\Invoice;
use Illuminate\Contracts\Console\Kernel;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripReceipt;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$companyId = 1;

$missingLorryTrips = [103, 131, 310, 326, 328, 333, 348];
$missingInvoiceTrips = [157, 158, 159, 321, 337, 339, 340, 341, 345, 346, 347, 348];

echo "=========================================================================================\n";
echo "      DIAGNOSIS: CANDIDATE DOCUMENTS IN DATABASE FOR TRIPS MISSING RECEIPTS\n";
echo "=========================================================================================\n\n";

// 1. Check for Missing Lorry Receipts candidates
echo "1. CHECKING FOR UNLINKED LORRY RECEIPTS (CHALLANS):\n";
echo str_repeat('-', 90)."\n";

$alreadyLinkedLorryIds = TripReceipt::query()
    ->where('company_id', $companyId)
    ->where('type', TripReceipt::TYPE_LORRY)
    ->pluck('invoice_id')
    ->all();

foreach ($missingLorryTrips as $tripNo) {
    $trip = Trip::query()->where('company_id', $companyId)->where('trip_no', $tripNo)->with('receipts')->first();
    if (! $trip) {
        continue;
    }
    $lrNos = $trip->receipts->where('type', 'lr')->pluck('invoice_number')->filter()->all();

    // Check if any lorry_receipt exists matching truck or bilty
    $candidates = Invoice::query()
        ->where('company_id', $companyId)
        ->where('template_name', 'lorry_receipt')
        ->whereNotIn('id', $alreadyLinkedLorryIds)
        ->where(function ($q) use ($lrNos, $trip) {
            foreach ($lrNos as $num) {
                $q->orWhere('tr_bilty_numbers', 'LIKE', "%{$num}%")
                    ->orWhere('notes', 'LIKE', "%{$num}%");
            }
            if ($trip->lorry_no) {
                $cleanLorry = str_replace(' ', '', $trip->lorry_no);
                $q->orWhere('tr_lorry_no', 'LIKE', "%{$cleanLorry}%");
            }
        })
        ->get(['id', 'invoice_number', 'invoice_date', 'tr_lorry_no', 'tr_bilty_numbers']);

    if ($candidates->isNotEmpty()) {
        $cList = $candidates->map(fn ($c) => "Lorry #{$c->invoice_number} (Truck: {$c->tr_lorry_no}, Bilty: {$c->tr_bilty_numbers})")->implode(', ');
        echo "Trip #{$tripNo} (LR: ".implode(', ', $lrNos)."): FOUND CANDIDATES -> {$cList}\n";
    } else {
        echo "Trip #{$tripNo} (LR: ".implode(', ', $lrNos)."): No candidate Lorry Receipt found in DB (needs to be created)\n";
    }
}

echo "\n".str_repeat('=', 90)."\n\n";

// 2. Check for Missing Invoice Receipts candidates
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

    // Look for Invoice Receipts where items match these LR numbers
    $candidates = Invoice::query()
        ->where('company_id', $companyId)
        ->where('template_name', 'invoice_receipt')
        ->whereNotIn('id', $alreadyLinkedInvoiceIds)
        ->whereHas('items', function ($q) use ($lrNos) {
            $q->whereIn('name', $lrNos)
                ->orWhereIn('tr_consignment_number', $lrNos);
            foreach ($lrNos as $num) {
                $q->orWhere('name', 'LIKE', "%{$num}%")
                    ->orWhere('tr_consignment_number', 'LIKE', "%{$num}%");
            }
        })
        ->get(['id', 'invoice_number', 'invoice_date', 'customer_id']);

    if ($candidates->isNotEmpty()) {
        $cList = $candidates->map(fn ($c) => "Invoice #{$c->invoice_number}")->implode(', ');
        echo "Trip #{$tripNo} (LR: ".implode(', ', $lrNos)."): FOUND CANDIDATE INVOICE -> {$cList}\n";
    } else {
        echo "Trip #{$tripNo} (LR: ".implode(', ', $lrNos)."): No candidate Invoice found in DB (Customer has not been billed yet)\n";
    }
}

echo str_repeat('=', 90)."\n";
