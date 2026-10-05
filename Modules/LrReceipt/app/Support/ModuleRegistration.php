<?php

declare(strict_types=1);

namespace Modules\LrReceipt\Support;

use App\Domains\Sales\Models\Invoice;
// HisabKitab feature
use App\Support\ModuleExtensions;
use InvoiceShelf\Modules\Registry;

/**
 * Registers the module's scripts, styles, and abilities with the host.
 */
final class ModuleRegistration
{
    public static function register(string $modulePath): void
    {
        Registry::registerScript('lr-receipt', $modulePath.'/dist/init.js');
        Registry::registerStyle('lr-receipt', $modulePath.'/dist/style.css');

        self::registerMenu();
        self::registerAbilities();
        self::registerModuleExtensions();
        self::publishPdfTemplate($modulePath);
    }

    private static function registerMenu(): void
    {
        Registry::registerMenu('lr-receipt', [
            'title' => 'LR Receipts',
            'link' => '/admin/invoices?view=lr_receipt',
            'icon' => 'ClipboardDocumentListIcon',
            'group' => 'documents',
            'group_label' => 'navigation.documents',
            'priority' => 25,
        ]);
    }

    private static function registerAbilities(): void
    {
        Registry::registerAbility('lr-receipt', [
            'ability' => 'view-lr-receipt',
            'name' => 'View LR Receipts',
            // LR Receipts are invoices, so the role must also see invoices.
            'depends_on' => ['view-invoice'],
        ]);
    }

    /**
     * HisabKitab feature — register backend extensions with the host.
     */
    private static function registerModuleExtensions(): void
    {
        // Serial number type for LR Receipt
        ModuleExtensions::registerSerialNumberType('lr_receipt', Invoice::class, [
            'type' => Invoice::TYPE_INVOICE,
            'template_name' => 'lr_receipt',
        ]);

        // Dashboard count provider
        ModuleExtensions::registerDashboardCountProvider('lr-receipt', function (int $companyId): array {
            $count = Invoice::query()
                ->where('company_id', $companyId)
                ->where('type', Invoice::TYPE_INVOICE)
                ->where('template_name', 'lr_receipt')
                ->count();

            return [[
                'key' => 'lr_receipt',
                'label' => $count === 1 ? 'LR Receipt' : 'LR Receipts',
                'to' => '/admin/invoices?view=lr_receipt',
                'value' => $count,
            ]];
        });

        // Invoice resource fields — append all tr_* columns
        ModuleExtensions::registerInvoiceResourceFields('lr-receipt', function ($invoice): array {
            return collect($invoice->getAttributes())
                ->filter(fn ($value, $key) => str_starts_with($key, 'tr_'))
                ->all();
        });

        // Invoice item resource fields — append all tr_* columns
        ModuleExtensions::registerInvoiceItemResourceFields('lr-receipt', function ($item): array {
            return collect($item->getAttributes())
                ->filter(fn ($value, $key) => str_starts_with($key, 'tr_'))
                ->all();
        });
    }

    /**
     * Copy the PDF blade template to the pdf_templates custom disk so the
     * host's PdfTemplateUtils::resolveView() can find it.
     */
    private static function publishPdfTemplate(string $modulePath): void
    {
        $source = $modulePath.'/resources/views/pdf/lr_receipt.blade.php';
        $dest = storage_path('app/templates/pdf/invoice/lr_receipt.blade.php');

        if (! file_exists($dest) && file_exists($source)) {
            @mkdir(dirname($dest), 0755, true);
            @copy($source, $dest);
        }
    }
}
