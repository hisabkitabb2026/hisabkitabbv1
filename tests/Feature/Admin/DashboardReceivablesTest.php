<?php

use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\User;
use App\Domains\Sales\Models\Invoice;
use App\Support\ModuleExtensions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    Carbon::setTestNow('2026-06-15 10:00:00');

    $user = User::find(1);
    $this->companyId = $user->companies()->first()->id;
    $this->withHeaders(['company' => $this->companyId]);
    Sanctum::actingAs($user, ['*']);
});

afterEach(function () {
    Carbon::setTestNow();
    ModuleExtensions::flush();
});

/**
 * An issued invoice owing $baseDue in the company currency, due on $dueDate.
 */
function dashboardReceivable(?string $dueDate, int $baseDue, array $overrides = []): Invoice
{
    return Invoice::factory()->create(array_merge([
        'status' => Invoice::STATUS_SENT,
        'type' => Invoice::TYPE_INVOICE,
        'due_date' => $dueDate,
        'due_amount' => $baseDue,
        'base_due_amount' => $baseDue,
    ], $overrides));
}

function dashboardReceivables(): array
{
    return getJson('api/v1/dashboard')->assertOk()->json('receivables');
}

test('the dashboard splits what is owed into overdue, due soon and due later', function () {
    $before = dashboardReceivables();

    dashboardReceivable('2026-06-14', 1000);              // yesterday: overdue
    dashboardReceivable('2026-03-01', 2500);              // months late: overdue
    dashboardReceivable('2026-06-15', 400);               // due today: not overdue yet
    dashboardReceivable('2026-07-15', 700);               // day 30: still "soon"
    dashboardReceivable('2026-07-16', 900);               // day 31: later
    dashboardReceivable(null, 300);                       // no due date: later

    $after = dashboardReceivables();

    expect($after['overdue'] - $before['overdue'])->toBe(3500)
        ->and($after['overdue_count'] - $before['overdue_count'])->toBe(2)
        ->and($after['due_soon'] - $before['due_soon'])->toBe(1100)
        ->and($after['due_later'] - $before['due_later'])->toBe(1200)
        ->and($after['outstanding'] - $before['outstanding'])->toBe(5800)
        ->and($after['outstanding_count'] - $before['outstanding_count'])->toBe(6);
});

test('drafts, settled invoices and credit notes are not receivables', function () {
    $before = dashboardReceivables();

    dashboardReceivable('2026-06-01', 5000, ['status' => Invoice::STATUS_DRAFT]);
    dashboardReceivable('2026-06-01', 0, ['paid_status' => Invoice::STATUS_PAID]);
    dashboardReceivable('2026-06-01', 800, ['type' => Invoice::TYPE_CREDIT_NOTE]);

    expect(dashboardReceivables())->toEqual($before);
});

test('a partly paid invoice counts what is still owed, in the company currency', function () {
    $before = dashboardReceivables();

    // 1,000.00 in the customer's currency at a rate of 1.25 leaves 1,250.00
    // owed in the company's; the dashboard speaks the company's currency.
    dashboardReceivable('2026-06-01', 125000, [
        'paid_status' => Invoice::STATUS_PARTIALLY_PAID,
        'due_amount' => 100000,
        'exchange_rate' => 1.25,
    ]);

    $after = dashboardReceivables();

    expect($after['overdue'] - $before['overdue'])->toBe(125000);
});

test('another company\'s invoices stay out of the figures', function () {
    $before = dashboardReceivables();

    $other = Company::factory()->create();
    dashboardReceivable('2026-06-01', 9000, ['company_id' => $other->id]);

    expect(dashboardReceivables())->toEqual($before);
});

/**
 * An issued invoice from one template, dated in June 2026 and worth $baseTotal in the company currency.
 */
function dashboardSale(string $template, int $baseTotal): Invoice
{
    return Invoice::factory()->create([
        'status' => Invoice::STATUS_SENT,
        'type' => Invoice::TYPE_INVOICE,
        'template_name' => $template,
        'invoice_date' => '2026-06-10',
        'total' => $baseTotal,
        'base_total' => $baseTotal,
        'due_amount' => 0,
        'base_due_amount' => 0,
        'paid_status' => Invoice::STATUS_PAID,
    ]);
}

function dashboardSales(): int
{
    return getJson('api/v1/dashboard?from_date=2026-06-01&to_date=2026-06-30')->assertOk()->json('total_sales');
}

test('with no receipt module on, a standard invoice is a receivable', function () {
    $before = dashboardReceivables();

    dashboardReceivable('2026-06-01', 1000, ['template_name' => 'invoice1']);

    expect(dashboardReceivables()['outstanding'] - $before['outstanding'])->toBe(1000);
});

test('once Invoice Receipt is on, a standard invoice stops being a receivable', function () {
    ModuleExtensions::registerReceiptModule('invoice-receipt');
    ModuleExtensions::registerSalesTemplate('invoice-receipt', 'invoice_receipt');
    $before = dashboardReceivables();

    dashboardReceivable('2026-06-01', 1000, ['template_name' => 'invoice1']);
    dashboardReceivable('2026-06-01', 2000, ['template_name' => 'invoice_receipt']);

    expect(dashboardReceivables()['outstanding'] - $before['outstanding'])->toBe(2000);
});

test('a Lorry Receipt is a hire owed to the owner, never a receivable', function () {
    ModuleExtensions::registerReceiptModule('invoice-receipt');
    ModuleExtensions::registerSalesTemplate('invoice-receipt', 'invoice_receipt');
    ModuleExtensions::registerReceiptModule('lorry-receipt');
    ModuleExtensions::registerSupplierTemplate('lorry-receipt', 'lorry_receipt');
    $before = dashboardReceivables();

    dashboardReceivable('2026-06-01', 1500, ['template_name' => 'lorry_receipt']);

    expect(dashboardReceivables())->toEqual($before);
});

test('an LR Receipt is not a customer receivable even though it replaces the standard invoice', function () {
    ModuleExtensions::registerReceiptModule('lr-receipt');
    $before = dashboardReceivables();

    dashboardReceivable('2026-06-01', 700, ['template_name' => 'lr_receipt']);
    dashboardReceivable('2026-06-01', 1000, ['template_name' => 'invoice1']);

    expect(dashboardReceivables())->toEqual($before);
});

test('with only the Lorry Receipt module on, there are no customer receivables', function () {
    ModuleExtensions::registerReceiptModule('lorry-receipt');
    ModuleExtensions::registerSupplierTemplate('lorry-receipt', 'lorry_receipt');
    $before = dashboardReceivables();

    dashboardReceivable('2026-06-01', 1000, ['template_name' => 'invoice1']);
    dashboardReceivable('2026-06-01', 1500, ['template_name' => 'lorry_receipt']);

    expect(dashboardReceivables())->toEqual($before);
});

test('the amount due and the invoice count follow the same receipt rule', function () {
    ModuleExtensions::registerReceiptModule('invoice-receipt');
    ModuleExtensions::registerSalesTemplate('invoice-receipt', 'invoice_receipt');
    ModuleExtensions::registerReceiptModule('lorry-receipt');
    ModuleExtensions::registerSupplierTemplate('lorry-receipt', 'lorry_receipt');
    $before = getJson('api/v1/dashboard')->assertOk()->json();

    dashboardReceivable('2026-06-01', 1000, ['template_name' => 'invoice1']);
    dashboardReceivable('2026-06-01', 2000, ['template_name' => 'invoice_receipt']);
    dashboardReceivable('2026-06-01', 4000, ['template_name' => 'lorry_receipt']);

    $after = getJson('api/v1/dashboard')->assertOk()->json();

    expect($after['total_amount_due'] - $before['total_amount_due'])->toBe(2000)
        ->and($after['total_invoice_count'] - $before['total_invoice_count'])->toBe(1);
});

test('the due invoices list leaves out lorry receipts', function () {
    ModuleExtensions::registerReceiptModule('invoice-receipt');
    ModuleExtensions::registerSalesTemplate('invoice-receipt', 'invoice_receipt');
    ModuleExtensions::registerReceiptModule('lorry-receipt');
    ModuleExtensions::registerSupplierTemplate('lorry-receipt', 'lorry_receipt');

    dashboardReceivable('2026-06-01', 4000, ['template_name' => 'lorry_receipt']);
    $due = dashboardReceivable('2026-06-01', 2000, ['template_name' => 'invoice_receipt']);

    $ids = collect(getJson('api/v1/dashboard')->assertOk()->json('recent_due_invoices'))->pluck('id');

    expect($ids)->toContain($due->id)->toHaveCount(1);
});

test('Sales counts the customer receipts only once a receipt module is on', function () {
    ModuleExtensions::registerReceiptModule('invoice-receipt');
    ModuleExtensions::registerSalesTemplate('invoice-receipt', 'invoice_receipt');
    ModuleExtensions::registerReceiptModule('lorry-receipt');
    ModuleExtensions::registerSupplierTemplate('lorry-receipt', 'lorry_receipt');
    $before = dashboardSales();

    dashboardSale('invoice1', 1000);
    dashboardSale('lorry_receipt', 4000);
    dashboardSale('invoice_receipt', 2500);

    expect(dashboardSales() - $before)->toBe(2500);
});

test('Sales counts standard invoices while no receipt module is on', function () {
    $before = dashboardSales();

    dashboardSale('invoice1', 1000);

    expect(dashboardSales() - $before)->toBe(1000);
});
