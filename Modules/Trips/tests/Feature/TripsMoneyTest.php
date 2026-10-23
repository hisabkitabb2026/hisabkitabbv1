<?php

// HisabKitab feature

use App\Domains\Accounts\Models\User;
use App\Domains\Sales\Models\Invoice;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\LorryReceipt\Application\LorryReceiptPayablesService;
use Modules\LorryReceipt\Models\LorryPartyProfile;
use Modules\Trips\Application\TripService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->user = User::where('email', 'admin@invoiceshelf.com')->firstOrFail();
    $this->companyId = (int) $this->user->companies()->firstOrFail()->id;

    // Neither module is enabled in the test application, so their migrations
    // do not run with the host's; run them for this suite.
    Artisan::call('migrate', ['--path' => 'Modules/LrReceipt/database/migrations', '--force' => true]);
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

it('handles multi-LR trips where different lorry receipts match different LRs and sums total cost and revenue', function () {
    $lr1 = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'template_name' => 'lr_receipt',
        'invoice_number' => 'LR-101',
        'total' => 1500000,
        'tr_from_name' => 'Mumbai',
        'tr_to_name' => 'Delhi',
    ]);

    $lr2 = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'template_name' => 'lr_receipt',
        'invoice_number' => 'LR-102',
        'total' => 2000000,
        'tr_from_name' => 'Mumbai',
        'tr_to_name' => 'Delhi',
    ]);

    $lorry1 = lorryReceiptInvoice([
        'company_id' => $this->companyId,
        'invoice_number' => 'LORRY-01',
        'tr_received_no_bilties' => 'LR-101',
        'tr_lorry_hire_amount' => '10000',
        'tr_other_charges_amount' => '0',
        'tr_advance_amount' => null,
        'total' => 0,
    ]);

    $lorry2 = lorryReceiptInvoice([
        'company_id' => $this->companyId,
        'invoice_number' => 'LORRY-02',
        'tr_received_no_bilties' => 'LR-102',
        'tr_lorry_hire_amount' => '12000',
        'tr_other_charges_amount' => '0',
        'tr_advance_amount' => null,
        'total' => 0,
    ]);

    $trips = app(TripService::class);

    // 1. Create trip from multiple LRs
    $trip = $trips->createFromLrInvoices($this->companyId, (int) $this->user->id, [$lr1->id, $lr2->id]);

    expect($trip->receipts->where('type', 'lr')->count())->toBe(2)
        ->and($trip->receipts->where('type', 'lorry')->count())->toBe(2);

    $lorryNumbers = $trip->receipts->where('type', 'lorry')->pluck('invoice_number')->all();
    expect($lorryNumbers)->toContain('LORRY-01')
        ->and($lorryNumbers)->toContain('LORRY-02');

    // 2. Check financial calculations
    $money = $trips->money($trip->refresh()->load(['receipts', 'expenses']));

    expect($money['revenue'])->toBe(3500000) // 15,000 + 20,000
        ->and($money['cost'])->toBe(2200000)    // 10,000 + 12,000
        ->and($money['profit'])->toBe(1300000)  // 35,000 - 22,000
        ->and($money['owner_balance'])->toBe(2200000);
});

it('fetches and links multiple different lorry receipts matching trip LRs on demand', function () {
    $lr1 = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'template_name' => 'lr_receipt',
        'invoice_number' => 'LR-201',
        'total' => 1000000,
    ]);

    $lr2 = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'template_name' => 'lr_receipt',
        'invoice_number' => 'LR-202',
        'total' => 1200000,
    ]);

    $lorry1 = lorryReceiptInvoice([
        'company_id' => $this->companyId,
        'invoice_number' => 'LORRY-10',
        'tr_received_no_bilties' => 'LR-201',
        'tr_lorry_hire_amount' => '8000',
        'tr_other_charges_amount' => '0',
        'tr_advance_amount' => null,
    ]);

    $lorry2 = lorryReceiptInvoice([
        'company_id' => $this->companyId,
        'invoice_number' => 'LORRY-20',
        'tr_received_no_bilties' => 'LR-202',
        'tr_lorry_hire_amount' => '9000',
        'tr_other_charges_amount' => '0',
        'tr_advance_amount' => null,
    ]);

    $trips = app(TripService::class);
    $trip = $trips->create($this->companyId, (int) $this->user->id, []);

    // Link LR receipts
    $trips->linkReceipt($this->companyId, (int) $this->user->id, $trip, $lr1->id, 'lr');
    $trips->linkReceipt($this->companyId, (int) $this->user->id, $trip, $lr2->id, 'lr');

    expect($trip->receipts()->where('type', 'lorry')->count())->toBe(0);

    // Fetch lorry receipts on demand
    $trip = $trips->fetchLorryReceipts($this->companyId, (int) $this->user->id, $trip);

    expect($trip->receipts->where('type', 'lorry')->count())->toBe(2);

    $lorryNumbers = $trip->receipts->where('type', 'lorry')->pluck('invoice_number')->all();
    expect($lorryNumbers)->toContain('LORRY-10')
        ->and($lorryNumbers)->toContain('LORRY-20');
});
