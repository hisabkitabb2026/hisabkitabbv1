<?php

declare(strict_types=1);

use App\Domains\Receivables\Models\Payment;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$payments = Payment::with(['customer', 'allocations.invoice'])
    ->where('notes', 'LIKE', '%TDS%')
    ->orWhere('notes', 'LIKE', '%tds%')
    ->orderBy('payment_date', 'desc')
    ->get();

echo "\n============================================================\n";
echo "  CUSTOMER PAYMENTS WITH TDS IN NOTES (Total: {$payments->count()})\n";
echo "============================================================\n\n";

if ($payments->isEmpty()) {
    echo "No payments found with 'TDS' or 'tds' in notes.\n\n";
    exit(0);
}

foreach ($payments as $p) {
    $custName = $p->customer->name ?? 'N/A';
    $amountPaid = number_format($p->amount / 100, 2);
    $notes = trim((string) $p->notes);

    echo "Payment ID:     #{$p->id}\n";
    echo "Payment Number: {$p->payment_number}\n";
    echo "Payment Date:   {$p->payment_date}\n";
    echo "Customer:       {$custName} (ID: {$p->customer_id})\n";
    echo "Amount Paid:    Rs. {$amountPaid}\n";
    echo "Notes:          \"{$notes}\"\n";

    if ($p->allocations->isNotEmpty()) {
        echo "Allocated Invoices:\n";
        foreach ($p->allocations as $alloc) {
            $inv = $alloc->invoice;
            if ($inv) {
                $template = $inv->template_name ?: 'standard';
                $invTotal = number_format($inv->total / 100, 2);
                $allocAmt = number_format($alloc->amount / 100, 2);
                $dueAmt = number_format($inv->due_amount / 100, 2);
                echo "   * Invoice: {$inv->invoice_number} [{$template}] | Total: Rs. {$invTotal} | Allocated: Rs. {$allocAmt} | Due: Rs. {$dueAmt} | Status: {$inv->paid_status}\n";
            }
        }
    } else {
        echo "Allocated Invoices: None (Unallocated / Credit)\n";
    }

    echo "------------------------------------------------------------\n";
}

echo "\nDone.\n";
