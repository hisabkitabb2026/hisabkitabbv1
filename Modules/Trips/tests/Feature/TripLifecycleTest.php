<?php

declare(strict_types=1);

use App\Domains\Accounts\Models\User;
use App\Domains\Receivables\Models\Payment;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Sales\Models\InvoiceItem;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Trips\Application\TripLifecycleService;
use Modules\Trips\Application\TripService;
use Modules\Trips\Application\TripStatusService;
use Modules\Trips\Models\Trip;
use Modules\Trips\Models\TripReceipt;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    $this->user = User::where('email', 'admin@invoiceshelf.com')->firstOrFail();
    $this->companyId = (int) $this->user->companies()->firstOrFail()->id;

    Artisan::call('migrate', ['--path' => 'Modules/LrReceipt/database/migrations', '--force' => true]);
    Artisan::call('migrate', ['--path' => 'Modules/LorryReceipt/database/migrations', '--force' => true]);
    Artisan::call('migrate', ['--path' => 'Modules/Trips/database/migrations', '--force' => true]);

    Relation::morphMap(['trip' => Trip::class]);

    app(TripStatusService::class)->ensureDefaults($this->companyId);
});

it('automatically creates a booked trip when an LR receipt is created', function () {
    $lifecycle = app(TripLifecycleService::class);

    $lr = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'template_name' => 'lr_receipt',
        'invoice_number' => '101',
        'invoice_date' => '2026-10-15',
        'tr_from_name' => 'Vapi',
        'tr_to_name' => 'Ghaziabad',
        'tr_description_goods' => 'Industrial Chemicals',
        'tr_truck_no' => 'GJ15AX4066',
        'total' => 2500000,
    ]);

    $trip = $lifecycle->handleLrReceipt($lr);

    expect($trip)->not->toBeNull();
    expect($trip->trip_no)->toBe(101);
    expect($trip->from_city)->toBe('Vapi');
    expect($trip->to_city)->toBe('Ghaziabad');
    expect($trip->status->code)->toBe('booked');

    $receipt = $trip->receipts()->where('type', TripReceipt::TYPE_LR)->first();
    expect($receipt)->not->toBeNull();
    expect($receipt->invoice_id)->toBe($lr->id);
    expect($receipt->amount)->toBe(2500000);
});

it('links lorry receipt and sets in_transit status, merging multiple LR trips', function () {
    $lifecycle = app(TripLifecycleService::class);

    $lr1 = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'template_name' => 'lr_receipt',
        'invoice_number' => '101',
        'total' => 1500000,
    ]);
    $lr2 = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'template_name' => 'lr_receipt',
        'invoice_number' => '102',
        'total' => 2000000,
    ]);

    $trip1 = $lifecycle->handleLrReceipt($lr1);
    $trip2 = $lifecycle->handleLrReceipt($lr2);

    expect($trip1->id)->not->toBe($trip2->id);

    $lorry = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'template_name' => 'lorry_receipt',
        'invoice_number' => 'LORRY-501',
        'tr_lorry_no' => 'HR55AH3040',
        'tr_received_no_bilties' => '101 102',
        'total' => 3000000,
    ]);

    $mergedTrip = $lifecycle->handleLorryReceipt($lorry);

    expect($mergedTrip)->not->toBeNull();
    expect($mergedTrip->trip_no)->toBe(101);
    expect($mergedTrip->status->code)->toBe('in_transit');
    expect($mergedTrip->lorry_no)->toBe('HR55AH3040');

    // Trip2 was merged into Trip1
    expect(Trip::query()->find($trip2->id))->toBeNull();

    $lrs = $mergedTrip->receipts()->where('type', TripReceipt::TYPE_LR)->pluck('invoice_number')->all();
    expect($lrs)->toContain('101');
    expect($lrs)->toContain('102');

    $lorryReceipt = $mergedTrip->receipts()->where('type', TripReceipt::TYPE_LORRY)->first();
    expect($lorryReceipt)->not->toBeNull();
    expect($lorryReceipt->invoice_id)->toBe($lorry->id);
});

it('links invoice receipt, advances status to delivered, and computes payment received', function () {
    $lifecycle = app(TripLifecycleService::class);
    $tripService = app(TripService::class);

    $lr = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'template_name' => 'lr_receipt',
        'invoice_number' => '101',
        'total' => 2500000,
    ]);
    $trip = $lifecycle->handleLrReceipt($lr);

    $officeInvoice = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'template_name' => 'invoice_receipt',
        'invoice_number' => 'INV-134',
        'total' => 2800000,
        'due_amount' => 2800000,
    ]);

    InvoiceItem::factory()->create([
        'invoice_id' => $officeInvoice->id,
        'company_id' => $this->companyId,
        'name' => 'Consignment 1',
        'tr_consignment_number' => '101',
        'total' => 2800000,
    ]);

    $updatedTrip = $lifecycle->handleInvoiceReceipt($officeInvoice);

    expect($updatedTrip->status->code)->toBe('delivered');
    expect($updatedTrip->receipts()->where('type', TripReceipt::TYPE_INVOICE)->exists())->toBeTrue();

    // Check money before payment
    $moneyBefore = $tripService->money($updatedTrip);
    expect($moneyBefore['revenue'])->toBe(2800000);
    expect($moneyBefore['customer_paid'])->toBe(0);
    expect($moneyBefore['customer_due'])->toBe(2800000);
    expect($moneyBefore['payment_status'])->toBe('UNPAID');

    // Simulate payment received
    $officeInvoice->update(['due_amount' => 0]);
    $payment = Payment::factory()->create([
        'company_id' => $this->companyId,
        'payment_number' => 'PAY-001',
        'amount' => 2800000,
    ]);

    $allocation = $payment->allocations()->create([
        'payment_id' => $payment->id,
        'invoice_id' => $officeInvoice->id,
        'amount' => 2800000,
        'base_amount' => 2800000,
    ]);

    $lifecycle->syncFromPaymentAllocation($allocation);

    $moneyAfter = $tripService->money($updatedTrip->refresh());
    expect($moneyAfter['customer_paid'])->toBe(2800000);
    expect($moneyAfter['customer_due'])->toBe(0);
    expect($moneyAfter['payment_status'])->toBe('PAID');

    // Check payment event logged
    expect($updatedTrip->events()->where('type', 'payment_received')->exists())->toBeTrue();
});
