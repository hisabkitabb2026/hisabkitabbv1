<?php

declare(strict_types=1);

namespace Modules\InvoiceReceipt\Providers;

use Illuminate\Contracts\Foundation\Application;
use InvoiceShelf\Modules\Contracts\DataCleanup;
use InvoiceShelf\Modules\Contracts\Host\SettingsStore;
use InvoiceShelf\Modules\Support\ModuleServiceProvider;
use Modules\InvoiceReceipt\Lifecycle\DataCleanup as InvoiceReceiptDataCleanup;
use Modules\InvoiceReceipt\Support\ModuleRegistration;

/**
 * Invoice Receipt (office invoice) module for InvoiceShelf.
 *
 * Invoice Receipts are invoices with template_name = 'invoice_receipt'. This module
 * adds a custom PDF template for formal invoicing.
 */
class InvoiceReceiptServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'InvoiceReceipt';

    protected string $nameLower = 'invoicereceipt';

    public function register(): void
    {
        parent::register();

        $this->app->bind(DataCleanup::class, fn (Application $app): DataCleanup => new InvoiceReceiptDataCleanup(
            $app->make(SettingsStore::class),
        ));
    }

    public function boot(): void
    {
        parent::boot();

        $modulePath = module_path($this->name);

        ModuleRegistration::register($modulePath);

        $this->loadRoutesFrom($modulePath.'/routes/api.php');

        $this->loadViewsFrom($modulePath.'/resources/views', 'invoicereceipt');
    }
}
