<?php

declare(strict_types=1);

namespace Modules\ChangeName\Providers;

use Illuminate\Contracts\Foundation\Application;
use InvoiceShelf\Modules\Contracts\DataCleanup;
use InvoiceShelf\Modules\Support\ModuleServiceProvider;
use Modules\ChangeName\Lifecycle\DataCleanup as ChangeNameDataCleanup;
use Modules\ChangeName\Support\ModuleRegistration;

/**
 * Rebrands the application from "InvoiceShelf" to "HisabKitab".
 *
 * This module overrides branding config at runtime (register) and sets
 * database-backed settings via migration. When the module is disabled,
 * the config overrides stop applying and the host's hardcoded defaults
 * return — no host file changes are needed.
 */
class ChangeNameServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ChangeName';

    protected string $nameLower = 'changename';

    public function register(): void
    {
        parent::register();

        // Override branding config at runtime — no host file changes.
        // These take effect only while the module is enabled; disabling
        // the module removes the ServiceProvider from the boot stack and
        // the host's config defaults return.
        config([
            'invoiceshelf.powered_by.name' => 'HisabKitab',
            'invoiceshelf.powered_by.url' => 'https://hisabkitab.com',
            'invoiceshelf.source_url' => 'https://github.com/HisabKitab/HisabKitab/tree/{version}',
        ]);

        $this->app->bind(
            DataCleanup::class,
            fn (Application $app): DataCleanup => new ChangeNameDataCleanup,
        );
    }

    public function boot(): void
    {
        parent::boot();

        $modulePath = module_path($this->name);

        ModuleRegistration::register($modulePath);
    }
}
