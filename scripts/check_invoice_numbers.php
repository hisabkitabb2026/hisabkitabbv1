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

echo "=========================================================================================\n";
echo "              INVOICE NUMBER SEARCH & TRIP 2655 DETAILS                                 \n";
echo "=========================================================================================\n\n";

// 1. Check if 2273, 2024, 2025 exist in ANY form in the invoices table
$searchNumbers = ['2273', '2024', '2025', '2264', '2018', '2457'];
foreach ($searchNumbers as $num) {
    $matches = DB::table('invoices')
        ->where('invoice_number', 'like', "%{$num}%")
        ->get(['id', 'invoice_number', 'template_name', 'company_id']);
    echo "Search for '{$num}':\n";
    if ($matches->isEmpty()) {
        echo "  [NOT FOUND] No invoice with number containing '{$num}'\n";
    } else {
        foreach ($matches as $m) {
            echo "  --> ID: {$m->id} | Number: '{$m->invoice_number}' | Template: {$m->template_name} | Company: {$m->company_id}\n";
        }
    }
}

// 2. Check Invoice ID 646 (linked as LR on Trip 2655)
echo "\nDetails of Invoice ID 646 (linked to Trip 2655):\n";
$inv646 = Invoice::find(646);
if ($inv646) {
    echo "  - Invoice Number: {$inv646->invoice_number}\n";
    echo "  - Template Name: {$inv646->template_name}\n";
    echo "  - Company ID: {$inv646->company_id}\n";
    echo "  - Date: {$inv646->invoice_date}\n";
    echo "  - Total: {$inv646->total}\n";
    echo "  - Customer ID: {$inv646->customer_id}\n";
    echo "  - From: {$inv646->tr_from_name} -> To: {$inv646->tr_to_name}\n";
    echo "  - Truck: {$inv646->tr_truck_no}\n";
    echo "  - Goods: {$inv646->tr_description_goods}\n";
    echo "  - Bilty / Consignment fields: party_inv: '{$inv646->tr_party_invoice_no}'\n";
} else {
    echo "  [NOT FOUND]\n";
}

// 3. Check Lorry Challan 1933 (Invoice ID 263) on Trip 2655
echo "\nDetails of Lorry Challan 1933 (Invoice ID 263):\n";
$inv263 = Invoice::find(263);
if ($inv263) {
    echo "  - Invoice Number: {$inv263->invoice_number}\n";
    echo "  - Lorry No: {$inv263->tr_lorry_no}\n";
    echo "  - Received Bilties: '{$inv263->tr_received_no_bilties}'\n";
    echo "  - From: {$inv263->tr_from_name} -> To: {$inv263->tr_to_name}\n";
}

echo "\n=========================================================================================\n";
