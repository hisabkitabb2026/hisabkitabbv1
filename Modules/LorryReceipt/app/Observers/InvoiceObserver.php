<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Observers;

use App\Domains\Purchases\Application\PurchaseDocumentService;
use App\Domains\Purchases\Models\Bill;
use App\Domains\Sales\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Modules\LorryReceipt\Application\LorryReceiptPayablesService;
use Silber\Bouncer\BouncerFacade;

/**
 * Mirrors lorry receipts into the purchasing tables as they are saved.
 *
 * The receipt itself is the printed document; the money it represents — the
 * hire owed to the owner and the payments made against it — is written to the
 * host's Supplier/Bill tables by LorryReceiptPayablesService. A failure here
 * must never take the receipt down, so everything is caught and logged.
 */
class InvoiceObserver
{
    public function __construct(private readonly LorryReceiptPayablesService $payables) {}

    /**
     * A receipt always carries a page count; the form no longer asks for one,
     * so it is fixed at 1 whenever it is saved without a value. The financial
     * totals (sub_total, total, due_amount) are also recalculated from the
     * Hire Particulars and Final Payment Particulars tr_ columns.
     */
    public function creating(Invoice $invoice): void
    {
        $this->defaultPages($invoice);
        $this->recalculateTotals($invoice);
    }

    public function updating(Invoice $invoice): void
    {
        $this->defaultPages($invoice);
        $this->recalculateTotals($invoice);
    }

    public function created(Invoice $invoice): void
    {
        $this->sync($invoice);
    }

    public function updated(Invoice $invoice): void
    {
        $this->sync($invoice);
    }

    /**
     * A deleted receipt voids its bill, so payables stop counting a hire that
     * no longer exists. A bill with payments cannot be voided; it stays, as
     * money really moved.
     */
    public function deleted(Invoice $invoice): void
    {
        if ($invoice->template_name !== 'lorry_receipt' || ! $invoice->tr_bill_id) {
            return;
        }

        try {
            $bill = Bill::query()->forCompany((int) $invoice->company_id)->find($invoice->tr_bill_id);

            if ($bill !== null && $bill->status !== 'VOID') {
                app(PurchaseDocumentService::class)
                    ->act($bill, 'void', 'Lorry receipt '.$invoice->invoice_number.' deleted');
            }
        } catch (\Throwable $e) {
            Log::warning('lorry-receipt: could not void the bill of deleted receipt '.$invoice->id.': '.$e->getMessage());
        }
    }

    private function defaultPages(Invoice $invoice): void
    {
        if ($invoice->template_name !== 'lorry_receipt') {
            return;
        }

        if ($invoice->tr_no_of_pages === null || trim((string) $invoice->tr_no_of_pages) === '') {
            $invoice->tr_no_of_pages = '1';
        }
    }

    /**
     * Recalculate the core financial columns from the tr_ Hire Particulars
     * and Final Payment Particulars.
     *
     * Hire Particulars (filled at receipt creation):
     *   Total = tr_lorry_hire_amount + tr_other_charges_amount
     *   Amount Due = Total - tr_advance_amount
     *
     * Final Payment Particulars (filled when vehicle arrives):
     *   When tr_net_amount_payable is filled, the balance is settled —
     *   Amount Due becomes 0.
     *
     * All tr_ money fields are stored as rupees (string/int). The core
     * columns are in minor units (paise), so we multiply by 100.
     */
    private function recalculateTotals(Invoice $invoice): void
    {
        if ($invoice->template_name !== 'lorry_receipt') {
            return;
        }

        $hire = $this->toPaise($invoice->tr_lorry_hire_amount);
        $otherCharges = $this->toPaise($invoice->tr_other_charges_amount);
        $advance = $this->toPaise($invoice->tr_advance_amount);

        $total = $hire + $otherCharges;

        // Balance remaining after advance
        $balance = max(0, $total - $advance);
        $invoice->tr_balance_amount = (string) round($balance / 100, 2);

        // Additional charges and deductions in final payment section
        $detention = $this->toPaise($invoice->tr_detention_amount);
        $extraHire = $this->toPaise($invoice->tr_extra_hire_amount);
        $finalOther = $this->toPaise($invoice->tr_final_other_amount);
        $lessAdv = $this->toPaise($invoice->tr_less_advance_other_branch_amount);
        $lessDed = $this->toPaise($invoice->tr_less_deduction_claims_amount);

        $netPayableTotal = max(0, $total + $detention + $extraHire + $finalOther - $advance - $lessAdv - $lessDed);

        $finalPaid = $this->toPaise($invoice->tr_final_amount_paid);
        $hasFinalSettlement = ! empty($invoice->tr_final_balance_on) || ! empty($invoice->tr_final_payment_id) || $finalPaid > 0;

        // Due amount stays pending until final settlement details are recorded
        if ($finalPaid > 0) {
            $dueAmount = max(0, $netPayableTotal - $finalPaid);
        } elseif ($hasFinalSettlement) {
            $dueAmount = 0;
        } else {
            $dueAmount = $balance;
        }

        $rate = (float) ($invoice->exchange_rate ?? 1);

        $invoice->sub_total = $total;
        $invoice->total = $total;
        $invoice->due_amount = $dueAmount;
        $invoice->base_sub_total = (int) round($total / $rate);
        $invoice->base_total = (int) round($total / $rate);
        $invoice->base_due_amount = (int) round($dueAmount / $rate);

        // Set status/paid_status to match the due amount and payment progress:
        // - dueAmount = 0 and at least one payment made → COMPLETED / PAID
        // - advance or partial final payment made but balance remains → SENT / PARTIALLY_PAID
        // - nothing paid → SENT / UNPAID
        if ($dueAmount === 0 && ($advance > 0 || $hasFinalSettlement)) {
            $invoice->status = Invoice::STATUS_COMPLETED;
            $invoice->paid_status = Invoice::STATUS_PAID;
        } else {
            $invoice->status = Invoice::STATUS_SENT;
            $invoice->paid_status = ($advance > 0 || $finalPaid > 0)
                ? Invoice::STATUS_PARTIALLY_PAID
                : Invoice::STATUS_UNPAID;
        }
    }

    /**
     * Convert a tr_ money value (rupees as string/int/float) to paise (minor units).
     */
    private function toPaise(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        $numeric = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return (int) round(((float) $numeric) * 100);
    }

    private function sync(Invoice $invoice): void
    {
        if ($invoice->template_name !== 'lorry_receipt') {
            return;
        }

        if (! $this->canWriteBills($invoice)) {
            return;
        }

        try {
            $this->payables->syncForInvoice($invoice);
        } catch (\Throwable $e) {
            Log::warning('lorry-receipt: payables sync failed for invoice '.$invoice->id.': '.$e->getMessage());
        }
    }

    /**
     * The host's own permission model for the purchasing side: creating the
     * bill needs create-bill, restating one needs edit-bill. Without the
     * ability the receipt still saves; only its payable is skipped.
     */
    private function canWriteBills(Invoice $invoice): bool
    {
        $ability = $invoice->tr_bill_id ? 'edit-bill' : 'create-bill';

        if (BouncerFacade::can($ability, Bill::class)) {
            return true;
        }

        Log::info("lorry-receipt: skipping payables sync, the member lacks the {$ability} ability");

        return false;
    }
}
