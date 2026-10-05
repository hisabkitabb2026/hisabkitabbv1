<?php

declare(strict_types=1);

namespace Modules\LrReceipt\Providers;

use Illuminate\Contracts\Foundation\Application;
use InvoiceShelf\Modules\Contracts\DataCleanup;
use InvoiceShelf\Modules\Contracts\Host\SettingsStore;
use InvoiceShelf\Modules\Support\ModuleServiceProvider;
use Modules\LrReceipt\Lifecycle\DataCleanup as LrReceiptDataCleanup;
use Modules\LrReceipt\Support\ModuleRegistration;

/**
 * LR Receipt (consignment docket) module for InvoiceShelf.
 *
 * LR Receipts are invoices with template_name = 'lr_receipt'. This module
 * adds transport-specific form fields, AI auto-fill, customer portal pages,
 * and a custom PDF template.
 */
class LrReceiptServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'LrReceipt';

    protected string $nameLower = 'lrreceipt';

    public function register(): void
    {
        parent::register();

        $this->app->bind(DataCleanup::class, fn (Application $app): DataCleanup => new LrReceiptDataCleanup(
            $app->make(SettingsStore::class),
        ));
    }

    public function boot(): void
    {
        parent::boot();

        $modulePath = module_path($this->name);

        ModuleRegistration::register($modulePath);

        $this->loadRoutesFrom($modulePath.'/routes/api.php');

        $this->loadViewsFrom($modulePath.'/resources/views', 'lrreceipt');
    }
}
