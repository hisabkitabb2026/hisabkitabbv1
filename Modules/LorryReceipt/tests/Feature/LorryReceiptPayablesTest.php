<?php

// HisabKitab feature

use App\Domains\Accounts\Models\User;
use App\Domains\Purchases\Application\PurchaseInputs;
use App\Domains\Purchases\Application\SupplierService;
use App\Domains\Purchases\Models\Bill;
use App\Domains\Purchases\Models\Supplier;
use App\Domains\Purchases\Models\SupplierPayment;
use App\Domains\Reporting\Queries\PurchasesQuery;
use App\Domains\Sales\Models\Invoice;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Modules\LorryReceipt\Application\LorryReceiptPayablesService;
use Modules\LorryReceipt\Http\Requests\LorryPartyProfileRequest;
use Modules\LorryReceipt\Models\LorryPartyProfile;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->user = User::where('email', 'admin@invoiceshelf.com')->firstOrFail();
    $this->companyId = (int) $this->user->companies()->firstOrFail()->id;

    // The module is not enabled in the test application, so its migrations do
    // not run with the host's; run them for this suite.
    Artisan::call('migrate', ['--path' => 'Modules/LorryReceipt/database/migrations', '--force' => true]);
});

function lorryParty(array $overrides = []): LorryPartyProfile
{
    return LorryPartyProfile::create(array_merge([
        'company_id' => test()->companyId,
        'type' => 'OWNER',
        'name' => 'Ramesh',
        'phone' => '9876543210',
        'address' => 'Vapi',
    ], $overrides));
}

function lorryReceipt(array $overrides = []): Invoice
{
    return Invoice::factory()->create(array_merge([
        'template_name' => 'lorry_receipt',
        'invoice_date' => '2026-10-01',
        'tr_owner_name' => 'Ramesh',
        'tr_owner_phone' => '9876543210',
        'tr_lorry_no' => 'MH01AB1234',
        'tr_lorry_hire_amount' => '18000',
        'tr_other_charges_amount' => '500',
        'tr_advance_amount' => '5000',
        'tr_advance_on' => '2026-10-02',
    ], $overrides));
}

it('links a party profile to a supplier and keeps it in step', function () {
    $profile = lorryParty();

    $supplier = app(LorryReceiptPayablesService::class)->ensureSupplier($profile);

    expect($profile->refresh()->supplier_id)->toBe($supplier->id)
        ->and($supplier->name)->toBe('Ramesh')
        ->and($supplier->phone)->toBe('9876543210')
        ->and($supplier->payment_terms)->toBe(30)
        ->and($supplier->addresses)->toBe([['address_street_1' => 'Vapi']]);

    $profile->update(['name' => 'Rameshbhai']);
    app(LorryReceiptPayablesService::class)->ensureSupplier($profile);

    expect(Supplier::count())->toBe(1)
        ->and($profile->refresh()->supplier->name)->toBe('Rameshbhai');
});

it('mirrors a lorry receipt into an open bill and an advance payment', function () {
    $profile = lorryParty();
    $invoice = lorryReceipt(['tr_owner_profile_id' => $profile->id]);

    app(LorryReceiptPayablesService::class)->syncForInvoice($invoice);

    $invoice->refresh();
    $bill = Bill::find($invoice->tr_bill_id);

    expect($bill)->not->toBeNull()
        ->and($bill->status)->toBe('OPEN')
        ->and($bill->total)->toBe(1850000)
        ->and($bill->due_amount)->toBe(1350000)
        ->and($bill->supplier_id)->toBe($profile->refresh()->supplier_id)
        ->and($invoice->tr_advance_payment_id)->not->toBeNull();

    expect(SupplierPayment::count())->toBe(1)
        ->and((int) $bill->paymentAllocations()->sum('amount'))->toBe(500000);

    $payables = app(PurchasesQuery::class)->payables($this->companyId);

    expect($payables['outstanding'])->toBe(1350000)
        ->and($payables['outstanding_count'])->toBe(1);
});

it('records the final balance and settles the bill', function () {
    $profile = lorryParty();
    $invoice = lorryReceipt([
        'tr_owner_profile_id' => $profile->id,
        'tr_final_balance_on' => '2026-10-10',
    ]);

    app(LorryReceiptPayablesService::class)->syncForInvoice($invoice);

    $invoice->refresh();
    $bill = Bill::find($invoice->tr_bill_id);

    expect($bill->due_amount)->toBe(0)
        ->and($bill->settlement_status)->toBe('SETTLED')
        ->and($invoice->tr_final_payment_id)->not->toBeNull()
        ->and(SupplierPayment::count())->toBe(2);
});

it('syncs again without duplicating anything', function () {
    $profile = lorryParty();
    $invoice = lorryReceipt(['tr_owner_profile_id' => $profile->id]);

    app(LorryReceiptPayablesService::class)->syncForInvoice($invoice);
    app(LorryReceiptPayablesService::class)->syncForInvoice($invoice);

    expect(Bill::count())->toBe(1)
        ->and(SupplierPayment::count())->toBe(1)
        ->and(Supplier::count())->toBe(1);
});

it('creates a supplier from the owner name when no profile is linked', function () {
    $invoice = lorryReceipt();

    app(LorryReceiptPayablesService::class)->syncForInvoice($invoice);

    $invoice->refresh();

    expect(Supplier::count())->toBe(1)
        ->and(Supplier::first()->name)->toBe('Ramesh')
        ->and($invoice->tr_bill_id)->not->toBeNull();
});

it('ignores receipts that are not lorry receipts', function () {
    $invoice = Invoice::factory()->create([
        'template_name' => 'invoice1',
        'tr_owner_name' => 'Ramesh',
        'tr_lorry_hire_amount' => '100',
    ]);

    app(LorryReceiptPayablesService::class)->syncForInvoice($invoice);

    expect(Bill::count())->toBe(0)
        ->and(Supplier::count())->toBe(0)
        ->and(SupplierPayment::count())->toBe(0);
});

it('skips receipts with nothing to owe', function () {
    $profile = lorryParty();
    $invoice = lorryReceipt([
        'tr_owner_profile_id' => $profile->id,
        'tr_lorry_hire_amount' => null,
        'tr_other_charges_amount' => null,
        'tr_advance_amount' => null,
    ]);

    app(LorryReceiptPayablesService::class)->syncForInvoice($invoice);

    expect(Bill::count())->toBe(0)
        ->and(SupplierPayment::count())->toBe(0);
});

function hostSupplier(string $name): Supplier
{
    return app(SupplierService::class)->save(
        null,
        test()->companyId,
        null,
        [
            'name' => $name,
            'currency_id' => PurchaseInputs::companyCurrency(test()->companyId),
            'payment_terms' => 15,
        ],
    );
}

it('links a party to an existing supplier instead of creating a new one', function () {
    $supplier = hostSupplier('Acme Transport');

    $profile = lorryParty(['name' => 'Acme Transport']);
    app(LorryReceiptPayablesService::class)->linkSupplier($profile, (int) $supplier->id);

    expect($profile->refresh()->supplier_id)->toBe($supplier->id)
        ->and(Supplier::count())->toBe(1);
});

it('stores the email on the supplier and keeps it on later syncs', function () {
    $profile = lorryParty();
    $service = app(LorryReceiptPayablesService::class);

    $service->ensureSupplier($profile, 'ramesh@example.com');
    expect(Supplier::first()->email)->toBe('ramesh@example.com');

    // A later update without an email keeps what is stored.
    $profile->update(['phone' => '9999999999']);
    $service->ensureSupplier($profile);
    expect(Supplier::first()->phone)->toBe('9999999999')
        ->and(Supplier::first()->email)->toBe('ramesh@example.com');
});

it('rejects a supplier id that is not one of this company', function () {
    $request = LorryPartyProfileRequest::create(
        '/api/v1/lorry-receipts/lorry-party-profiles',
        'POST',
        ['type' => 'OWNER', 'name' => 'X', 'supplier_id' => 999999],
        [],
        [],
        ['HTTP_COMPANY' => (string) $this->companyId],
    );

    $validator = app('validator')->make($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('supplier_id'))->toBeTrue();
});
