<?php

declare(strict_types=1);

namespace Modules\Quotation\Support;

use App\Domains\Sales\Models\Estimate;
// HisabKitab feature
use App\Support\ModuleExtensions;
use InvoiceShelf\Modules\Registry;

/**
 * Registers the module's scripts, styles, menu, abilities and PDF template
 * with the host.
 */
final class ModuleRegistration
{
    public static function register(string $modulePath): void
    {
        Registry::registerScript('quotation', $modulePath.'/dist/init.js');
        Registry::registerStyle('quotation', $modulePath.'/dist/style.css');

        self::registerMenu();
        self::registerAbilities();
        self::registerModuleExtensions();
        self::publishPdfTemplate($modulePath);
    }

    private static function registerMenu(): void
    {
        Registry::registerMenu('quotation', [
            'title' => 'Quotations',
            'link' => '/admin/estimates?view=quotation',
            'icon' => 'DocumentTextIcon',
            'group' => 'documents',
            'group_label' => 'navigation.documents',
            'priority' => 25,
        ]);
    }

    private static function registerAbilities(): void
    {
        Registry::registerAbility('quotation', [
            'ability' => 'view-quotation',
            'name' => 'View Quotations',
            // Quotations are estimates, so the role must also see estimates.
            'depends_on' => ['view-estimate'],
        ]);
    }

    /**
     * HisabKitab feature — register backend extensions with the host.
     */
    private static function registerModuleExtensions(): void
    {
        // Serial number type for Quotation
        ModuleExtensions::registerSerialNumberType('quotation', Estimate::class, [
            'template_name' => 'quotation',
        ]);

        // Dashboard count provider for Quotations
        ModuleExtensions::registerDashboardCountProvider('quotation', function (int $companyId): array {
            $count = Estimate::query()
                ->where('company_id', $companyId)
                ->where('template_name', 'quotation')
                ->count();

            return [[
                'key' => 'quotation',
                'label' => $count === 1 ? 'Quotation' : 'Quotations',
                'to' => '/admin/estimates?view=quotation',
                'value' => $count,
            ]];
        });

        // Estimate item resource fields — append all tr_* columns
        ModuleExtensions::registerEstimateItemResourceFields('quotation', function ($item): array {
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
        $source = $modulePath.'/resources/views/pdf/quotation.blade.php';
        $dest = storage_path('app/templates/pdf/estimate/quotation.blade.php');

        if (! file_exists($dest) && file_exists($source)) {
            @mkdir(dirname($dest), 0755, true);
            @copy($source, $dest);
        }
    }
}
