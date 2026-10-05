<?php

declare(strict_types=1);

namespace Modules\Quotation\Providers;

use Illuminate\Contracts\Foundation\Application;
use InvoiceShelf\Modules\Contracts\DataCleanup;
use InvoiceShelf\Modules\Contracts\Host\SettingsStore;
use InvoiceShelf\Modules\Support\ModuleServiceProvider;
use Modules\Quotation\Lifecycle\DataCleanup as QuotationDataCleanup;
use Modules\Quotation\Support\ModuleRegistration;

/**
 * Quotation module for InvoiceShelf.
 *
 * Quotations are estimates with template_name = 'quotation'. This module adds a
 * redesigned consignment-style items table (Station Name + capacity dropdown)
 * injected into the host estimate form, plus a custom PDF template.
 */
class QuotationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Quotation';

    protected string $nameLower = 'quotation';

    public function register(): void
    {
        parent::register();

        $this->app->bind(DataCleanup::class, fn (Application $app): DataCleanup => new QuotationDataCleanup(
            $app->make(SettingsStore::class),
        ));
    }

    public function boot(): void
    {
        parent::boot();

        $modulePath = module_path($this->name);

        ModuleRegistration::register($modulePath);

        $this->loadRoutesFrom($modulePath.'/routes/api.php');

        $this->loadViewsFrom($modulePath.'/resources/views', 'quotation');
    }
}
