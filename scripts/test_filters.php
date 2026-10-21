<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSTIC TEST: FILTERS ===\n\n";

// 1. Inspect Invoice Receipts
echo "--- 1. INVOICE RECEIPTS & CONSIGNMENT NUMBERS ---\n";
$invoiceReceipts = Invoice::query()
    ->where('template_name', 'invoice_receipt')
    ->with('items')
    ->latest()
    ->limit(5)
    ->get();

echo "Found Invoice Receipts count: " . Invoice::query()->where('template_name', 'invoice_receipt')->count() . "\n";
$sampleConsignment = null;
foreach ($invoiceReceipts as $inv) {
    echo "Invoice ID: {$inv->id}, Inv No: {$inv->invoice_number}, Items: {$inv->items->count()}\n";
    foreach ($inv->items as $item) {
        echo "   -> Item ID: {$item->id}, Name: '{$item->name}', tr_consignment_number: '{$item->tr_consignment_number}'\n";
        if (!$sampleConsignment && $item->tr_consignment_number) {
            $sampleConsignment = trim($item->tr_consignment_number);
        }
    }
}

// 2. Inspect Lorry Receipts
echo "\n--- 2. LORRY RECEIPTS & RECEIVED NO OF BILTIES ---\n";
$lorryReceipts = Invoice::query()
    ->where('template_name', 'lorry_receipt')
    ->whereNotNull('tr_received_no_bilties')
    ->where('tr_received_no_bilties', '!=', '')
    ->latest()
    ->limit(5)
    ->get();

echo "Found Lorry Receipts with tr_received_no_bilties: {$lorryReceipts->count()}\n";
$sampleLr = null;
foreach ($lorryReceipts as $inv) {
    echo "Challan ID: {$inv->id}, Challan No: {$inv->invoice_number}, tr_received_no_bilties: '{$inv->tr_received_no_bilties}'\n";
    if (!$sampleLr && $inv->tr_received_no_bilties) {
        $sampleLr = trim($inv->tr_received_no_bilties);
    }
}

// 3. Test Query Scopes for Consignment Number
echo "\n--- 3. TEST QUERY: Consignment Number Filter ---\n";
if ($sampleConsignment) {
    echo "Testing with sample consignment number: '{$sampleConsignment}'\n";
    $q = Invoice::query()->applyFilters([
        'template_name' => 'invoice_receipt',
        'consignment_number' => $sampleConsignment,
    ]);
    echo "SQL: " . $q->toSql() . "\n";
    echo "Bindings: " . json_encode($q->getBindings()) . "\n";
    $results = $q->get();
    echo "Results count: {$results->count()}\n";
    foreach ($results as $r) {
        echo "   -> Match: ID {$r->id}, Inv No: {$r->invoice_number}\n";
    }
} else {
    echo "No sample consignment found in DB to test.\n";
}

// 4. Test Query Scopes for LR Number on Lorry Receipt
echo "\n--- 4. TEST QUERY: LR Number Filter on Lorry Receipt ---\n";
if ($sampleLr) {
    echo "Testing with sample LR number: '{$sampleLr}'\n";
    $q = Invoice::query()->applyFilters([
        'template_name' => 'lorry_receipt',
        'lr_number' => $sampleLr,
    ]);
    echo "SQL: " . $q->toSql() . "\n";
    echo "Bindings: " . json_encode($q->getBindings()) . "\n";
    $results = $q->get();
    echo "Results count: {$results->count()}\n";
    foreach ($results as $r) {
        echo "   -> Match: ID {$r->id}, Challan No: {$r->invoice_number}, Bilties: {$r->tr_received_no_bilties}\n";
    }
} else {
    echo "No sample LR found in DB to test.\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
