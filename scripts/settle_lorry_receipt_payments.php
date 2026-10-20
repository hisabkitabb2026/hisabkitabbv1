<?php

declare(strict_types=1);

/**
 * Script to settle remaining balance on Lorry Receipts for Company 1.
 *
 * For each Lorry Receipt:
 * 1. Calculates the remaining balance (Lorry Hire - Advance Paid).
 * 2. Populates Section E (Final Payment Particulars):
 *    - tr_final_paid_to
 *    - tr_final_amount_paid
 *    - tr_final_balance_on
 *    - tr_net_amount_payable
 * 3. Updates Invoice status to COMPLETED and paid_status to PAID with due_amount = 0.
 * 4. Calls LorryReceiptPayablesService to record the final payment against the Supplier Bill,
 *    settling the bill in full (due_amount = 0, status = PAID) and linking tr_final_payment_id.
 *
 * Usage:
 *   php scripts/settle_lorry_receipt_payments.php
 *   php scripts/settle_lorry_receipt_payments.php --challans=5,6,7,8
 */

use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\User;
use App\Domains\Purchases\Models\Bill;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LorryReceipt\Application\LorryReceiptPayablesService;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$companyId = 1;

// Ensure tr_final_amount_paid column exists on invoices table
if (! Schema::hasColumn('invoices', 'tr_final_amount_paid')) {
    Schema::table('invoices', function (Blueprint $table) {
        $table->text('tr_final_amount_paid')->nullable();
    });
}

// Authenticate as company owner/admin for Bouncer and settlement services
$company = Company::find($companyId);
$user = $company?->owner ?? $company?->users()->first() ?? User::query()->first();
if ($user) {
    auth()->login($user);
}

$payables = app(LorryReceiptPayablesService::class);

// Parse optional filter
$challanFilter = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--challans=')) {
        $challanFilter = array_filter(array_map('trim', explode(',', substr($arg, 11))));
    }
}

$query = Invoice::query()
    ->where('company_id', $companyId)
    ->where('template_name', 'lorry_receipt');

if ($challanFilter !== null && count($challanFilter) > 0) {
    $padded = array_map(fn ($c) => str_pad($c, 3, '0', STR_PAD_LEFT), $challanFilter);
    $allMatches = array_unique(array_merge($challanFilter, $padded));
    $query->whereIn('invoice_number', $allMatches);
} else {
    // By default, target any receipt that is not yet fully paid or has due_amount > 0
    $query->where(function ($q) {
        $q->where('paid_status', '!=', Invoice::STATUS_PAID)
            ->orWhere('due_amount', '>', 0)
            ->orWhereNull('tr_final_payment_id');
    });
}

$invoices = $query->orderBy(DB::raw('CAST(invoice_number AS UNSIGNED)'), 'asc')->get();

if ($invoices->isEmpty()) {
    echo "No unpaid or pending Lorry Receipts found to settle for Company {$companyId}.\n";
    exit(0);
}

echo "Found {$invoices->count()} Lorry Receipt(s) to settle for Company {$companyId}...\n\n";

$settledCount = 0;
$totalSettledAmount = 0;

foreach ($invoices as $invoice) {
    $challanNo = $invoice->invoice_number;

    // Determine hire, advance, and remaining balance
    $hire = (float) ($invoice->tr_lorry_hire_amount ?: ($invoice->total / 100));
    $advance = (float) ($invoice->tr_advance_amount ?: 0);
    $calculatedBalance = max(0, $hire - $advance);

    // If due_amount is already set, use it; otherwise use calculated balance
    $dueRs = $invoice->due_amount > 0 ? ($invoice->due_amount / 100) : $calculatedBalance;

    // Payment date: use advance date or invoice date
    $balanceDate = ! empty($invoice->tr_advance_on)
        ? substr((string) $invoice->tr_advance_on, 0, 10)
        : (! empty($invoice->invoice_date) ? substr((string) $invoice->invoice_date, 0, 10) : now()->toDateString());

    $payee = $invoice->tr_final_paid_to ?: ($invoice->tr_paid_to ?: ($invoice->tr_owner_name ?: 'Owner/Transporter'));

    DB::beginTransaction();
    try {
        // 1. Ensure creator_id is set
        if (! $invoice->creator_id) {
            $invoice->creator_id = $user?->id ?? 1;
        }

        // 2. Set Section E (Final Payment) attributes
        $invoice->tr_final_paid_to = $payee;
        $invoice->tr_final_amount_paid = (string) $dueRs;
        $invoice->tr_net_amount_payable = (string) $dueRs;
        $invoice->tr_final_balance_on = $balanceDate;

        // 3. Mark core sales document as COMPLETED & PAID with 0 due
        $invoice->due_amount = 0;
        $invoice->base_due_amount = 0;
        $invoice->status = Invoice::STATUS_COMPLETED;
        $invoice->paid_status = Invoice::STATUS_PAID;
        $invoice->saveQuietly();

        // 4. Sync purchasing payables to record final settlement payment against the Bill
        $payables->syncForInvoice($invoice);

        DB::commit();

        $invoice->refresh();

        $billStatus = 'N/A';
        if ($invoice->tr_bill_id) {
            $bill = Bill::find($invoice->tr_bill_id);
            $billStatus = $bill ? "Bill ID: {$bill->id} (Status: {$bill->status}, Due: Rs. ".($bill->due_amount / 100).')' : 'N/A';
        }

        echo "[✓] Challan [{$challanNo}] (Invoice ID: {$invoice->id}) | Paid To: {$payee} | Hire: Rs.{$hire} | Advance: Rs.{$advance} | Balance Paid: Rs.{$dueRs} | {$billStatus} | Final Payment ID: ".($invoice->tr_final_payment_id ?? 'None')."\n";

        $settledCount++;
        $totalSettledAmount += $dueRs;
    } catch (Throwable $e) {
        DB::rollBack();
        echo "[!] Error settling Challan {$challanNo}: ".$e->getMessage()."\n";
    }
}

echo "\n=========================================\n";
echo "Settlement Complete!\n";
echo "Total Lorry Receipts Settled: {$settledCount}\n";
echo 'Total Balance Amount Paid: Rs. '.number_format($totalSettledAmount, 2)."\n";
echo "All processed receipts and bills are now fully PAID (Due = Rs. 0).\n";
echo "=========================================\n";
