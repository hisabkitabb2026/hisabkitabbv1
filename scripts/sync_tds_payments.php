<?php

declare(strict_types=1);

use App\Domains\Receivables\Models\Payment;
use App\Domains\Sales\Application\InvoiceBalanceService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$balanceService = $app->make(InvoiceBalanceService::class);

$isDryRun = ! in_array('--execute', $argv, true);

echo "\n============================================================\n";
echo "  TDS HISTORICAL DATA SYNC SCRIPT\n";
echo '  Mode: '.($isDryRun ? 'DRY RUN (Use --execute to apply changes)' : 'EXECUTE (Applying changes to DB)')."\n";
echo "============================================================\n\n";

$payments = Payment::with(['customer', 'allocations.invoice'])
    ->where(function ($q) {
        $q->where('notes', 'LIKE', '%TDS%')
            ->orWhere('notes', 'LIKE', '%tds%');
    })
    ->orderBy('payment_date', 'asc')
    ->get();

if ($payments->isEmpty()) {
    echo "No payments found with TDS in notes.\n";
    exit(0);
}

$processed = 0;
$skipped = 0;
$totalTdsPaise = 0;

DB::beginTransaction();

try {
    foreach ($payments as $p) {
        $notes = (string) $p->notes;
        $custName = $p->customer->name ?? 'N/A';

        // Match regex: TDS or TDS TAX followed by optional arrows, colons, spaces, and the number
        // e.g. "TDS->2432", "TDS TAX -> 1229", "TDS: 1000", "tds->431"
        if (! preg_match('/TDS(?:[^\d]*?)(\d+(?:\.\d+)?)/i', $notes, $m)) {
            echo "[-] Payment #{$p->id} ({$p->payment_number}): Could not parse TDS from notes: \"{$notes}\". Skipping.\n";
            $skipped++;

            continue;
        }

        $tdsNumber = (float) $m[1];
        $tdsPaise = (int) round($tdsNumber * 100);

        if ($p->allocations->isEmpty()) {
            echo "[-] Payment #{$p->id} ({$p->payment_number}): No allocations found. Skipping.\n";
            $skipped++;

            continue;
        }

        $alloc = $p->allocations->first();
        $inv = $alloc->invoice;

        if (! $inv) {
            echo "[-] Payment #{$p->id} ({$p->payment_number}): Allocation invoice missing. Skipping.\n";
            $skipped++;

            continue;
        }

        $oldInvDue = $inv->due_amount;
        $oldInvStatus = $inv->paid_status;

        $shouldAdjustPaymentAmount = false;
        if ((int) $p->amount === ((int) $alloc->amount + $tdsPaise)) {
            $shouldAdjustPaymentAmount = true;
        } elseif ($inv && (int) $p->amount === (int) $inv->total && (int) $alloc->amount < (int) $p->amount) {
            $shouldAdjustPaymentAmount = true;
        }

        echo "[+] Payment #{$p->id} ({$p->payment_number}) | Customer: {$custName}\n";
        echo "    Note: \"{$notes}\"\n";
        echo '    Detected TDS: Rs. '.number_format($tdsNumber, 2)." ({$tdsPaise} paise)\n";
        echo "    Invoice: {$inv->invoice_number} | Total: Rs. ".number_format($inv->total / 100, 2).' | Due: Rs. '.number_format($oldInvDue / 100, 2)." | Status: {$oldInvStatus}\n";

        if (! $isDryRun) {
            if ($shouldAdjustPaymentAmount) {
                $p->amount = (int) $alloc->amount;
                $p->base_amount = (int) ($alloc->base_amount ?? $alloc->amount);
            }

            // Update payment TDS
            $p->tds_amount = $tdsPaise;
            $p->base_tds_amount = $tdsPaise;
            $p->save();

            // Update allocation TDS
            $alloc->tds_amount = $tdsPaise;
            $alloc->base_tds_amount = $tdsPaise;
            $alloc->save();

            // Recalculate invoice balance
            $balanceService->recalculate($inv);
            $inv->refresh();

            echo '    -> UPDATED: Payment TDS = Rs. '.number_format($tdsNumber, 2).' | Allocation TDS = Rs. '.number_format($tdsNumber, 2)."\n";
            if ($shouldAdjustPaymentAmount) {
                echo '    -> ADJUSTED: Payment Bank Received = Rs. '.number_format($p->amount / 100, 2)." (Net after TDS, eliminating unapplied credit)\n";
            }
            echo "    -> RECALCULATED: Invoice {$inv->invoice_number} New Due: Rs. ".number_format($inv->due_amount / 100, 2)." | New Status: {$inv->paid_status} ({$inv->status})\n";
        } else {
            $simulatedDue = max(0, $inv->due_amount - $tdsPaise);
            $simulatedStatus = $simulatedDue === 0 ? 'PAID (COMPLETED)' : 'PARTIALLY_PAID';
            $adjustMsg = $shouldAdjustPaymentAmount
                ? ' | Will adjust Payment Bank Amount to Rs. '.number_format($alloc->amount / 100, 2)
                : '';
            echo '    [Dry Run] Will set Payment & Allocation TDS = Rs. '.number_format($tdsNumber, 2).$adjustMsg."\n";
            echo "    [Dry Run] Invoice {$inv->invoice_number} will become Due: Rs. ".number_format($simulatedDue / 100, 2)." | Status: {$simulatedStatus}\n";
        }

        echo "------------------------------------------------------------\n";
        $processed++;
        $totalTdsPaise += $tdsPaise;
    }

    if (! $isDryRun) {
        DB::commit();
        echo "\nSUCCESS: Successfully committed changes to database!\n";
    } else {
        DB::rollBack();
        echo "\nDRY RUN COMPLETE: No changes were made to the database.\n";
        echo "Run with '--execute' to apply changes.\n";
    }

    echo "Total Payments Processed: {$processed}\n";
    echo "Total Payments Skipped:   {$skipped}\n";
    echo 'Total TDS Amount:         Rs. '.number_format($totalTdsPaise / 100, 2)."\n\n";

} catch (Throwable $e) {
    DB::rollBack();
    echo "\nERROR: ".$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
    exit(1);
}
