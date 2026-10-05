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
