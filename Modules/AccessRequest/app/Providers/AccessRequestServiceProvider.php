<?php

declare(strict_types=1);

namespace Modules\AccessRequest\Providers;

use InvoiceShelf\Modules\Contracts\DataCleanup;
use InvoiceShelf\Modules\Support\ModuleServiceProvider;
use Modules\AccessRequest\Lifecycle\DataCleanup as AccessRequestDataCleanup;
use Modules\AccessRequest\Support\ModuleRegistration;

/**
 * Access Request module for InvoiceShelf.
 *
 * Lets members ask the company owner for permissions via email.
 */
class AccessRequestServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'AccessRequest';

    protected string $nameLower = 'accessrequest';

    public function register(): void
    {
        parent::register();

        $this->app->bind(DataCleanup::class, fn (): DataCleanup => new AccessRequestDataCleanup);
    }

    public function boot(): void
    {
        parent::boot();

        $modulePath = module_path($this->name);

        ModuleRegistration::register($modulePath);

        $this->loadRoutesFrom($modulePath.'/routes/api.php');

        $this->loadViewsFrom($modulePath.'/resources/views', 'accessrequest');
    }
}
