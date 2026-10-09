<?php

declare(strict_types=1);

namespace Modules\Trips\Support;

use App\Support\ModuleExtensions;
use InvoiceShelf\Modules\Registry;

final class ModuleRegistration
{
    public static function register(string $modulePath): void
    {
        Registry::registerScript('trips', $modulePath.'/dist/init.js');
        Registry::registerStyle('trips', $modulePath.'/dist/style.css');

        self::registerMenu();
        self::registerAbilities();
        self::registerModuleExtensions();
    }

    /**
     * One sidebar entry, in the host's own main group, after Tasks.
     */
    private static function registerMenu(): void
    {
        Registry::registerMenu('trips', [
            'title' => 'trips::menu.trips',
            'link' => '/admin/modules/trips',
            'icon' => 'TruckIcon',
            'group' => 'main',
            'group_label' => '',
            'priority' => 60,
        ]);
    }

    /**
     * The module's own abilities, namespaced `trips:{ability}` by the registry.
     */
    private static function registerAbilities(): void
    {
        $view = Registry::abilityId(Abilities::SLUG, Abilities::VIEW_TRIP);

        $abilities = [
            [Abilities::VIEW_TRIP, 'View trips', []],
            [Abilities::CREATE_TRIP, 'Create trips', [$view]],
            [Abilities::EDIT_TRIP, 'Edit trips', [$view]],
            [Abilities::DELETE_TRIP, 'Delete trips', [$view]],
        ];

        foreach ($abilities as [$ability, $name, $dependsOn]) {
            Registry::registerAbility(Abilities::SLUG, [
                'ability' => $ability,
                'name' => $name,
                'depends_on' => $dependsOn,
            ]);
        }
    }

    /**
     * HisabKitab feature — register backend extensions with the host.
     */
    private static function registerModuleExtensions(): void
    {
        // Register the media library path folder for Trip model uploads.
        ModuleExtensions::registerMediaPath('trip', 'Trips');
    }
}
