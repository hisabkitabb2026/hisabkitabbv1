<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Support;

use App\Domains\Sales\Models\Invoice;
// HisabKitab feature
use App\Support\ModuleExtensions;
use InvoiceShelf\Modules\Registry;

/**
 * Registers the module's scripts, styles, menu, and abilities with the host.
 */
final class ModuleRegistration
{
    public static function register(string $modulePath): void
    {
        Registry::registerScript('lorry-receipt', $modulePath.'/dist/init.js');
        Registry::registerStyle('lorry-receipt', $modulePath.'/dist/style.css');

        self::registerMenu();
        self::registerAbilities();
        self::registerModuleExtensions();
        self::publishPdfTemplate($modulePath);
    }

    private static function registerMenu(): void
    {
        // Lorry Receipts sidebar entry — in the documents group, between Invoices (20) and Payments (30)
        Registry::registerMenu('lorry-receipt', [
            'title' => 'Lorry Receipts',
            'link' => '/admin/invoices?view=lorry_receipt',
            'icon' => 'TruckIcon',
            'group' => 'documents',
            'group_label' => 'navigation.documents',
            'priority' => 26,
        ]);
    }

    private static function registerAbilities(): void
    {
        Registry::registerAbility('lorry-receipt', [
            'ability' => 'view-lorry-receipt',
            'name' => 'View Lorry Receipts',
            // Lorry Receipts are invoices, so the role must also see invoices.
            'depends_on' => ['view-invoice'],
        ]);
        Registry::registerAbility('lorry-receipt', [
            'ability' => 'manage-party-profiles',
            'name' => 'Manage Party Profiles',
            'depends_on' => ['lorry-receipt:view-lorry-receipt'],
        ]);
    }

    /**
     * HisabKitab feature — register backend extensions with the host.
     */
    private static function registerModuleExtensions(): void
    {
        ModuleExtensions::registerSerialNumberType('lorry_receipt', Invoice::class, [
            'type' => Invoice::TYPE_INVOICE,
            'template_name' => 'lorry_receipt',
        ]);

        ModuleExtensions::registerDashboardCountProvider('lorry-receipt', function (int $companyId): array {
            $count = Invoice::query()
                ->where('company_id', $companyId)
                ->where('type', Invoice::TYPE_INVOICE)
                ->where('template_name', 'lorry_receipt')
                ->count();

            return [[
                'key' => 'lorry_receipt',
                'label' => $count === 1 ? 'Lorry Receipt' : 'Lorry Receipts',
                'to' => '/admin/invoices?view=lorry_receipt',
                'value' => $count,
            ]];
        });

        ModuleExtensions::registerInvoiceResourceFields('lorry-receipt', function ($invoice): array {
            return collect($invoice->getAttributes())
                ->filter(fn ($value, $key) => str_starts_with($key, 'tr_'))
                ->all();
        });

        ModuleExtensions::registerInvoiceItemResourceFields('lorry-receipt', function ($item): array {
            return collect($item->getAttributes())
                ->filter(fn ($value, $key) => str_starts_with($key, 'tr_'))
                ->all();
        });
    }

    private static function publishPdfTemplate(string $modulePath): void
    {
        $templates = [
            'lorry_receipt.blade.php',
        ];

        foreach ($templates as $template) {
            $source = $modulePath.'/resources/views/pdf/'.$template;
            $dest = storage_path('app/templates/pdf/invoice/'.$template);

            if (! file_exists($dest) && file_exists($source)) {
                @mkdir(dirname($dest), 0755, true);
                @copy($source, $dest);
            }
        }
    }
}
