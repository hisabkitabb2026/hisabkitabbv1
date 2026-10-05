<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Application;

use App\Domains\Purchases\Application\PurchaseDocumentService;
use App\Domains\Purchases\Application\PurchaseInputs;
use App\Domains\Purchases\Application\SupplierService;
use App\Domains\Purchases\Application\SupplierSettlementService;
use App\Domains\Purchases\Models\Bill;
use App\Domains\Purchases\Models\ExpenseCategory;
use App\Domains\Purchases\Models\Supplier;
use App\Domains\Sales\Models\Invoice;
use Carbon\CarbonImmutable;
use Modules\LorryReceipt\Models\LorryPartyProfile;

/**
 * The money side of a Lorry Receipt.
 *
 * The receipt itself (an invoice with template_name = 'lorry_receipt') stays
 * the printed document. This service mirrors what it represents financially
 * into the host's purchasing tables, so payables, supplier balances and cash
 * flow include lorry hires:
 *
 *   - the owner behind the receipt becomes a Supplier (created on first use,
 *     kept in step with the party profile's name, phone and address);
 *   - the hire owed becomes a Bill, one line, due after the supplier's payment
 *     terms, numbered by the host's purchasing number sequence;
 *   - the advance and the final balance become SupplierPayments allocated to
 *     that bill, so its status runs UNPAID -> PARTIAL -> SETTLED.
 *
 * Every piece is written once and remembered on the invoice row
 * (tr_bill_id, tr_advance_payment_id, tr_final_payment_id), so syncing again
 * is safe. The tr_ money fields are plain text holding major units; they are
 * read with amount() which converts to the integer minor units the purchasing
 * tables use.
 */
class LorryReceiptPayablesService
{
    /** The expense category lorry hire bills are filed under. */
    private const EXPENSE_CATEGORY = 'Lorry Hire';

    /** Payment terms given to a supplier created here, in days. */
    private const PAYMENT_TERMS = 30;

    public function __construct(
        private readonly SupplierService $suppliers,
        private readonly PurchaseDocumentService $documents,
        private readonly SupplierSettlementService $settlements,
    ) {}

    /**
     * The Supplier behind a party profile, created on first use and kept in
     * step with the profile's contact details. The email is only sent when
     * given, so a later update without one keeps what is stored.
     */
    public function ensureSupplier(LorryPartyProfile $profile, ?string $email = null): Supplier
    {
        $companyId = (int) $profile->company_id;

        $existing = $profile->supplier_id
            ? Supplier::query()->forCompany($companyId)->find($profile->supplier_id)
            : null;

        $attributes = [
            'name' => $profile->name,
            'phone' => $profile->phone,
            'currency_id' => PurchaseInputs::companyCurrency($companyId),
            'payment_terms' => self::PAYMENT_TERMS,
            'addresses' => $this->address($profile->address),
            'notes' => 'Lorry party ('.strtolower((string) $profile->type).')',
        ];

        if ($email !== null) {
            $attributes['email'] = $email;
        }

        $supplier = $this->suppliers->save($existing, $companyId, null, $attributes);

        if ($profile->supplier_id !== $supplier->id) {
            $profile->forceFill(['supplier_id' => $supplier->id])->saveQuietly();
        }

        return $supplier;
    }

    /**
     * Link a party profile to a Supplier the company already has (one picked
     * from the host's Suppliers list): no new supplier is created, the
     * profile points at the one given. The request rules already checked the
     * supplier belongs to this company.
     */
    public function linkSupplier(LorryPartyProfile $profile, int $supplierId): Supplier
    {
        $supplier = Supplier::query()
            ->forCompany((int) $profile->company_id)
            ->findOrFail($supplierId);

        if ($profile->supplier_id !== $supplier->id) {
            $profile->forceFill(['supplier_id' => $supplier->id])->saveQuietly();
        }

        return $supplier;
    }

    /**
     * Mirror one lorry receipt into the purchasing tables. Skips anything that
     * is not a lorry receipt, has no owner to owe, or nothing to owe.
     */
    public function syncForInvoice(Invoice $invoice): void
    {
        if ($invoice->template_name !== 'lorry_receipt') {
            return;
        }

        $supplier = $this->resolveSupplier($invoice);

        if ($supplier === null) {
            return;
        }

        $hire = $this->hireTotal($invoice);

        if ($hire <= 0) {
            return;
        }

        $companyId = (int) $invoice->company_id;
        $currencyId = PurchaseInputs::companyCurrency($companyId);
        $documentDate = $this->date($invoice->invoice_date);

        $bill = $this->syncBill($invoice, $supplier, $currencyId, $documentDate, $hire);
        $this->syncAdvancePayment($invoice, $supplier, $currencyId, $documentDate, $bill);
        $this->syncFinalPayment($invoice, $supplier, $currencyId, $bill);
    }

    /**
     * The supplier the hire is owed to: the owner profile selected on the
     * receipt, or a supplier named like the owner typed on it.
     */
    private function resolveSupplier(Invoice $invoice): ?Supplier
    {
        $companyId = (int) $invoice->company_id;

        if ($invoice->tr_owner_profile_id) {
            $profile = LorryPartyProfile::query()
                ->where('company_id', $companyId)
                ->find($invoice->tr_owner_profile_id);

            if ($profile !== null) {
                return $this->ensureSupplier($profile);
            }
        }

        $name = trim((string) $invoice->tr_owner_name);

        if ($name === '') {
            return null;
        }

        $existing = Supplier::query()->forCompany($companyId)->where('name', $name)->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->suppliers->save(null, $companyId, null, [
            'name' => $name,
            'phone' => $invoice->tr_owner_phone,
            'currency_id' => PurchaseInputs::companyCurrency($companyId),
            'payment_terms' => self::PAYMENT_TERMS,
            'addresses' => $this->address($invoice->tr_owner_address),
            'notes' => 'Created from lorry receipt '.$invoice->invoice_number,
        ]);
    }

    /**
     * Create the hire's bill, or restate an unsettled one when the receipt is
     * edited. A settled bill is locked by the host; the caller lets that
     * failure through to be logged.
     */
    private function syncBill(Invoice $invoice, Supplier $supplier, int $currencyId, string $documentDate, int $hire): Bill
    {
        $companyId = (int) $invoice->company_id;

        $existing = $invoice->tr_bill_id
            ? Bill::query()->forCompany($companyId)->find($invoice->tr_bill_id)
            : null;

        $dueDate = CarbonImmutable::parse($documentDate)
            ->addDays($supplier->payment_terms ?: self::PAYMENT_TERMS)
            ->toDateString();

        $bill = $this->documents->saveBill($existing, $companyId, $invoice->creator_id, [
            'supplier_id' => $supplier->id,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'reference' => 'Lorry receipt '.$invoice->invoice_number,
            'document_date' => $documentDate,
            'due_date' => $dueDate,
            'status' => 'OPEN',
            'notes' => trim('Lorry '.(string) $invoice->tr_lorry_no.' — hire from lorry receipt '.$invoice->invoice_number),
            'items' => [[
                'description' => 'Lorry hire'.($invoice->tr_lorry_no ? ' — '.(string) $invoice->tr_lorry_no : ''),
                'expense_category_id' => $this->expenseCategoryId($companyId),
                'quantity' => 1,
                'price' => $hire,
            ]],
        ]);

        if ($invoice->tr_bill_id !== $bill->id) {
            $invoice->forceFill(['tr_bill_id' => $bill->id])->saveQuietly();
        }

        return $bill;
    }

    /**
     * The advance paid on the receipt, recorded once and allocated to the
     * bill. Whatever cannot be allocated stays as an advance on the supplier.
     */
    private function syncAdvancePayment(Invoice $invoice, Supplier $supplier, int $currencyId, string $documentDate, Bill $bill): void
    {
        if ($invoice->tr_advance_payment_id) {
            return;
        }

        $advance = $this->amount($invoice->tr_advance_amount);

        if ($advance <= 0) {
            return;
        }

        $allocatable = min($advance, (int) $bill->due_amount);

        $payment = $this->settlements->recordPayment((int) $invoice->company_id, $invoice->creator_id, [
            'supplier_id' => $supplier->id,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'amount' => $advance,
            'payment_date' => $this->dateOr($invoice->tr_advance_on, $documentDate),
            'payment_method_id' => $invoice->tr_advance_payment_method_id,
            'reference' => $invoice->tr_advance_cash_cheque_no,
            'notes' => 'Advance against lorry receipt '.$invoice->invoice_number,
            'allocations' => $allocatable > 0 ? [['bill_id' => $bill->id, 'amount' => $allocatable]] : [],
        ]);

        $invoice->forceFill(['tr_advance_payment_id' => $payment->id])->saveQuietly();
    }

    /**
     * The final balance, recorded once when the receipt says it was paid, for
     * whatever is still due on the bill — which settles it.
     */
    private function syncFinalPayment(Invoice $invoice, Supplier $supplier, int $currencyId, Bill $bill): void
    {
        if ($invoice->tr_final_payment_id || ! $invoice->tr_final_balance_on) {
            return;
        }

        $bill->refresh();

        $due = (int) $bill->due_amount;

        if ($due <= 0) {
            return;
        }

        $payment = $this->settlements->recordPayment((int) $invoice->company_id, $invoice->creator_id, [
            'supplier_id' => $supplier->id,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'amount' => $due,
            'payment_date' => $this->dateOr($invoice->tr_final_balance_on, $this->date($invoice->invoice_date)),
            'payment_method_id' => $invoice->tr_final_payment_method_id,
            'reference' => $invoice->tr_final_cash_cheque_no,
            'notes' => 'Final balance on lorry receipt '.$invoice->invoice_number,
            'allocations' => [['bill_id' => $bill->id, 'amount' => $due]],
        ]);

        $invoice->forceFill(['tr_final_payment_id' => $payment->id])->saveQuietly();
    }

    /**
     * What the hire comes to before any payment, in minor units: the hire and
     * its additions, less the receipt's deductions.
     */
    private function hireTotal(Invoice $invoice): int
    {
        $gross = $this->amount($invoice->tr_lorry_hire_amount)
            + $this->amount($invoice->tr_other_charges_amount)
            + $this->amount($invoice->tr_detention_amount)
            + $this->amount($invoice->tr_extra_hire_amount)
            + $this->amount($invoice->tr_final_other_amount);

        $deductions = $this->amount($invoice->tr_less_advance_other_branch_amount)
            + $this->amount($invoice->tr_less_deduction_claims_amount);

        return max(0, $gross - $deductions);
    }

    /**
     * The company's "Lorry Hire" expense category, created on first use.
     */
    private function expenseCategoryId(int $companyId): int
    {
        $existing = ExpenseCategory::query()
            ->where('company_id', $companyId)
            ->where('name', self::EXPENSE_CATEGORY)
            ->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        return (int) ExpenseCategory::query()->create([
            'name' => self::EXPENSE_CATEGORY,
            'company_id' => $companyId,
            'description' => 'Lorry hire recorded on lorry receipts',
        ])->id;
    }

    /**
     * A tr_ money field (plain text, major units) as integer minor units.
     */
    private function amount(mixed $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    /**
     * Free text as the supplier address list the host expects.
     *
     * @return array<int, array<string, string>>
     */
    private function address(mixed $value): array
    {
        $street = trim((string) $value);

        return $street === '' ? [] : [['address_street_1' => $street]];
    }

    /**
     * A date-ish value as Y-m-d, or today when empty.
     */
    private function date(mixed $value): string
    {
        $text = trim((string) $value);

        return $text === '' ? now()->toDateString() : substr($text, 0, 10);
    }

    /**
     * A date-ish value as Y-m-d, or the fallback when empty.
     */
    private function dateOr(mixed $value, string $fallback): string
    {
        $text = trim((string) $value);

        return $text === '' ? $fallback : substr($text, 0, 10);
    }
}
