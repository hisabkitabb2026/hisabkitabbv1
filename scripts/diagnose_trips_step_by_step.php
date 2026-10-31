<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Domains\Sales\Models\Invoice;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripReceipt;

echo "=========================================================================================\n";
echo "              STEP-BY-STEP DIAGNOSTIC INSPECTION                                         \n";
echo "=========================================================================================\n\n";

// -----------------------------------------------------------------------------------------
// STEP 1: INSPECT THE 12 UNLINKED INVOICES
// -----------------------------------------------------------------------------------------
echo "-----------------------------------------------------------------------------------------\n";
echo "STEP 1: INSPECTING THE 12 UNLINKED INVOICE RECEIPTS\n";
echo "-----------------------------------------------------------------------------------------\n";

$linkedIds = DB::table('trip_receipts')->pluck('invoice_id')->all();
$unlinkedInvoices = Invoice::where('template_name', 'invoice_receipt')
    ->whereNotIn('id', $linkedIds)
    ->with('items')
    ->get();

echo "Found {$unlinkedInvoices->count()} unlinked invoice receipts.\n";

foreach ($unlinkedInvoices as $inv) {
    echo "\nInvoice #{$inv->invoice_number} (ID: {$inv->id}, Customer ID: {$inv->customer_id}):\n";
    $items = $inv->items;
    if ($items->isEmpty()) {
        echo "  - NO LINE ITEMS FOUND on this invoice!\n";
    } else {
        foreach ($items as $item) {
            $cNo = $item->tr_consignment_number ?? '-';
            echo "  - Item: '{$item->name}' | Consignment No: '{$cNo}' | Amount: {$item->total}\n";

            // Check if this item name or consignment number matches any LR in trip_receipts
            $matchedReceipt = TripReceipt::where('type', 'lr')
                ->where(function ($q) use ($item) {
                    $q->where('invoice_number', $item->name)
                        ->orWhere('invoice_number', $item->tr_consignment_number);
                })
                ->first();

            if ($matchedReceipt) {
                echo "    --> [MATCH FOUND!] Matches LR #{$matchedReceipt->invoice_number} in Trip ID {$matchedReceipt->trip_id}!\n";
            } else {
                echo "    --> [No match] No linked LR found matching '{$item->name}' or '{$cNo}'.\n";
            }
        }
    }
}

// -----------------------------------------------------------------------------------------
// STEP 2: INSPECT THE 19 STANDALONE LORRY TRIPS (2643 - 2659)
// -----------------------------------------------------------------------------------------
echo "\n-----------------------------------------------------------------------------------------\n";
echo "STEP 2: INSPECTING THE 19 STANDALONE LORRY TRIPS\n";
echo "-----------------------------------------------------------------------------------------\n";

$standaloneTrips = Trip::with('receipts')
    ->whereHas('receipts', fn ($q) => $q->where('type', 'lorry'))
    ->whereDoesntHave('receipts', fn ($q) => $q->where('type', 'lr'))
    ->get();

echo "Found {$standaloneTrips->count()} standalone lorry trips.\n\n";

foreach ($standaloneTrips as $trip) {
    $lorryReceipt = $trip->receipts->where('type', 'lorry')->first();
    $lorryInv = $lorryReceipt ? Invoice::find($lorryReceipt->invoice_id) : null;
    $bilty = $lorryInv ? trim((string) $lorryInv->tr_received_no_bilties) : '';

    echo "Trip #{$trip->trip_no} (ID: {$trip->id}) -> Lorry Challan: {$lorryReceipt?->invoice_number}\n";
    echo '  - Received Bilties on Challan: '.($bilty !== '' ? "'{$bilty}'" : '[EMPTY]')."\n";

    if ($bilty !== '') {
        $tokens = preg_split('/[\s,;]+/u', $bilty, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_map('trim', $tokens);

        // Check if these bilty numbers exist in any LR receipts
        $existingLrs = DB::table('invoices')
            ->where('template_name', 'lr_receipt')
            ->whereIn('invoice_number', $tokens)
            ->get();

        if ($existingLrs->isNotEmpty()) {
            foreach ($existingLrs as $lr) {
                $linkedTrip = DB::table('trip_receipts')->where('invoice_id', $lr->id)->first();
                $tripInfo = $linkedTrip ? "already on Trip ID {$linkedTrip->trip_id}" : 'UNLINKED LR';
                echo "    --> LR #{$lr->invoice_number} exists in database ({$tripInfo})\n";
            }
        } else {
            echo '    --> [No LR in DB] None of bilties ['.implode(', ', $tokens)."] exist as LR receipts in DB.\n";
        }
    }
}

// -----------------------------------------------------------------------------------------
// STEP 3: INSPECT TRIP # 2655 (BILTY MISMATCH)
// -----------------------------------------------------------------------------------------
echo "\n-----------------------------------------------------------------------------------------\n";
echo "STEP 3: INSPECTING TRIP # 2655 (BILTY MISMATCH)\n";
echo "-----------------------------------------------------------------------------------------\n";

$trip2655 = Trip::where('trip_no', 2655)->with('receipts')->first();
if ($trip2655) {
    echo "Trip #{$trip2655->trip_no} (ID: {$trip2655->id}):\n";
    foreach ($trip2655->receipts as $r) {
        $inv = Invoice::find($r->invoice_id);
        echo "  - Type: {$r->type} | Number: {$r->invoice_number} (Invoice ID: {$r->invoice_id})\n";
        if ($r->type === 'lorry') {
            echo "    Lorry Challan tr_received_no_bilties: '{$inv?->tr_received_no_bilties}'\n";
        }
    }
} else {
    echo "Trip #2655 not found.\n";
}

echo "\n=========================================================================================\n";
