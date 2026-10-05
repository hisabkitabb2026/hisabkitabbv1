<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Providers;

use App\Domains\Sales\Models\Invoice;
use Illuminate\Contracts\Foundation\Application;
use InvoiceShelf\Modules\Contracts\DataCleanup;
use InvoiceShelf\Modules\Contracts\Host\SettingsStore;
use InvoiceShelf\Modules\Support\ModuleServiceProvider;
use Modules\LorryReceipt\Lifecycle\DataCleanup as LorryReceiptDataCleanup;
use Modules\LorryReceipt\Observers\InvoiceObserver;
use Modules\LorryReceipt\Support\ModuleRegistration;

/**
 * Lorry Receipt (truck payment record) module for InvoiceShelf.
 */
class LorryReceiptServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'LorryReceipt';

    protected string $nameLower = 'lorryreceipt';

    public function register(): void
    {
        parent::register();

        $this->app->bind(DataCleanup::class, fn (Application $app): DataCleanup => new LorryReceiptDataCleanup(
            $app->make(SettingsStore::class),
        ));
    }

    public function boot(): void
    {
        parent::boot();

        $modulePath = module_path($this->name);

        ModuleRegistration::register($modulePath);

        $this->loadRoutesFrom($modulePath.'/routes/api.php');

        $this->loadViewsFrom($modulePath.'/resources/views', 'lorryreceipt');

        // Mirror lorry receipts into the purchasing tables (Supplier, Bill,
        // SupplierPayment) as they are saved.
        Invoice::observe(InvoiceObserver::class);
    }
}
