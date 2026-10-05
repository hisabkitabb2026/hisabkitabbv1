<?php

declare(strict_types=1);

namespace Modules\Trips\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Relations\Relation;
use InvoiceShelf\Modules\Contracts\DataCleanup as DataCleanupContract;
use InvoiceShelf\Modules\Support\ModuleServiceProvider;
use Modules\Trips\Lifecycle\DataCleanup as TripsDataCleanup;
use Modules\Trips\Models\Trip;
use Modules\Trips\Support\ModuleRegistration;

/**
 * Trips: the transport trip board module.
 */
class TripsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Trips';

    protected string $nameLower = 'trips';

    public function register(): void
    {
        parent::register();

        $this->app->bind(DataCleanupContract::class, fn (Application $app): DataCleanupContract => new TripsDataCleanup);
    }

    public function boot(): void
    {
        parent::boot();

        // The host enforces a morph map (ModelIdentityMap) that does not know
        // about module models. Spatie MediaLibrary needs to persist a polymorphic
        // record for the Trip model, so we merge our alias in here — enforceMorphMap
        // merges by default, and module providers boot after the host's AppServiceProvider.
        Relation::morphMap(['trip' => Trip::class]);

        $modulePath = module_path($this->name);

        ModuleRegistration::register($modulePath);

        $this->loadRoutesFrom($modulePath.'/routes/api.php');

        $this->loadViewsFrom($modulePath.'/resources/views', 'trips');
    }
}
