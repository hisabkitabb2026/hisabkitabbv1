<?php

// HisabKitab feature

use App\Domains\Accounts\Models\User;
use App\Domains\Sales\Models\Invoice;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Modules\LorryReceipt\Application\LorryReceiptPayablesService;
use Modules\LorryReceipt\Models\LorryPartyProfile;
use Modules\Trips\Application\TripService;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->user = User::where('email', 'admin@invoiceshelf.com')->firstOrFail();
    $this->companyId = (int) $this->user->companies()->firstOrFail()->id;

    // Neither module is enabled in the test application, so their migrations
    // do not run with the host's; run them for this suite.
    Artisan::call('migrate', ['--path' => 'Modules/LorryReceipt/database/migrations', '--force' => true]);
    Artisan::call('migrate', ['--path' => 'Modules/Trips/database/migrations', '--force' => true]);
});

function lorryReceiptInvoice(array $overrides = []): Invoice
{
    return Invoice::factory()->create(array_merge([
        'template_name' => 'lorry_receipt',
        'invoice_date' => '2026-10-01',
        'tr_owner_name' => 'Ramesh',
        'tr_lorry_no' => 'MH01AB1234',
        'tr_lorry_hire_amount' => '18000',
        'tr_other_charges_amount' => '500',
        'tr_advance_amount' => '5000',
        'tr_advance_on' => '2026-10-02',
    ], $overrides));
}

it('reads the money of a trip from the bill behind its lorry receipt', function () {
    $profile = LorryPartyProfile::create([
        'company_id' => $this->companyId,
        'type' => 'OWNER',
        'name' => 'Ramesh',
        'phone' => '9876543210',
    ]);

    $invoice = lorryReceiptInvoice(['tr_owner_profile_id' => $profile->id]);

    // The Lorry Receipt module's mirror: a bill for the hire, the advance
    // allocated to it.
    app(LorryReceiptPayablesService::class)->syncForInvoice($invoice);

    $trips = app(TripService::class);
    $trip = $trips->create($this->companyId, (int) $this->user->id, [
        'from_city' => 'Surat',
        'to_city' => 'Mumbai',
        'lorry_no' => 'MH01AB1234',
    ]);
    $trips->linkReceipt($this->companyId, (int) $this->user->id, $trip, $invoice->id, 'lorry');

    $money = $trips->money($trip->refresh()->load(['receipts', 'expenses']));

    expect($money['cost'])->toBe(1850000)
        ->and($money['owner_paid'])->toBe(500000)
        ->and($money['owner_balance'])->toBe(1350000)
        ->and($money['bill_id'])->not->toBeNull()
        ->and($money['bill_status'])->toBe('PARTIAL');
});

it('falls back to the receipt amount when the lorry receipt has no bill', function () {
    $invoice = lorryReceiptInvoice(['tr_lorry_hire_amount' => '9000', 'tr_advance_amount' => null]);

    $trips = app(TripService::class);
    $trip = $trips->create($this->companyId, (int) $this->user->id, []);
    $trips->linkReceipt($this->companyId, (int) $this->user->id, $trip, $invoice->id, 'lorry');

    $money = $trips->money($trip->refresh()->load(['receipts', 'expenses']));

    // The receipt's denormalized amount: the invoice total the form synced
    // (net payable), or the tr_ fields when it never had one.
    expect($money['owner_paid'])->toBe(0)
        ->and($money['bill_id'])->toBeNull()
        ->and($money['bill_status'])->toBeNull()
        ->and($money['owner_balance'])->toBe($money['cost']);
});

it('has no owner-payments routes left', function () {
    $routes = collect(app('router')->getRoutes()->getRoutesByName())->keys()
        ->filter(fn (string $name) => str_contains($name, 'trips.owner-payments'));

    expect($routes)->toBeEmpty();
});
