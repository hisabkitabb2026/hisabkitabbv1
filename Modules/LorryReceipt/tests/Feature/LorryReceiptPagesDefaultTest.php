<?php

// HisabKitab feature

use App\Domains\Sales\Models\Invoice;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\LorryReceipt\Observers\InvoiceObserver;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);

    // The module is not enabled in the test application, so its provider does
    // not attach the observer; attach it once for this process.
    static $observing = false;
    if (! $observing) {
        Invoice::observe(InvoiceObserver::class);
        $observing = true;
    }

    Artisan::call('migrate', ['--path' => 'Modules/LorryReceipt/database/migrations', '--force' => true]);
});

it('defaults the page count of a new lorry receipt to 1', function () {
    $receipt = Invoice::factory()->create(['template_name' => 'lorry_receipt']);

    expect($receipt->fresh()->tr_no_of_pages)->toBe('1');
});

it('keeps a page count that was given explicitly', function () {
    $receipt = Invoice::factory()->create([
        'template_name' => 'lorry_receipt',
        'tr_no_of_pages' => '3',
    ]);

    expect($receipt->fresh()->tr_no_of_pages)->toBe('3');
});

it('fills a blank page count when a lorry receipt is updated', function () {
    $receipt = Invoice::factory()->create(['template_name' => 'lorry_receipt']);

    $receipt->update(['tr_no_of_pages' => '', 'tr_received_no_bilties' => 'LR-778']);

    expect($receipt->fresh())
        ->tr_no_of_pages->toBe('1')
        ->tr_received_no_bilties->toBe('LR-778');
});

it('leaves other invoices without a page count', function () {
    $invoice = Invoice::factory()->create(['template_name' => 'invoice1']);

    expect($invoice->fresh()->tr_no_of_pages)->toBeNull();
});

it('backfills existing lorry receipts that have no page count', function () {
    $blank = Invoice::factory()->create(['template_name' => 'lorry_receipt']);
    $other = Invoice::factory()->create(['template_name' => 'invoice1']);

    DB::table('invoices')->whereIn('id', [$blank->id, $other->id])->update(['tr_no_of_pages' => null]);

    $migration = require base_path('Modules/LorryReceipt/database/migrations/2026_10_10_094826_default_lorry_receipt_no_of_pages_to_one.php');
    $migration->up();

    expect(DB::table('invoices')->where('id', $blank->id)->value('tr_no_of_pages'))->toBe('1')
        ->and(DB::table('invoices')->where('id', $other->id)->value('tr_no_of_pages'))->toBeNull();
});
