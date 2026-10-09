<?php

declare(strict_types=1);

namespace Modules\InvoiceReceipt\Support;

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
        Registry::registerScript('invoice-receipt', $modulePath.'/dist/init.js');
        Registry::registerStyle('invoice-receipt', $modulePath.'/dist/style.css');

        self::registerMenu();
        self::registerAbilities();
        self::registerModuleExtensions();
        self::publishPdfTemplate($modulePath);
    }

    private static function registerMenu(): void
    {
        Registry::registerMenu('invoice-receipt', [
            'title' => 'Invoice Receipts',
            'link' => '/admin/invoices?view=invoice_receipt',
            'icon' => 'DocumentTextIcon',
            'group' => 'documents',
            'group_label' => 'navigation.documents',
            'priority' => 24,
        ]);
    }

    private static function registerAbilities(): void
    {
        Registry::registerAbility('invoice-receipt', [
            'ability' => 'view-invoice-receipt',
            'name' => 'View Invoice Receipts',
            // Invoice Receipts are invoices, so the role must also see invoices.
            'depends_on' => ['view-invoice'],
        ]);
    }

    /**
     * HisabKitab feature — register backend extensions with the host.
     */
    private static function registerModuleExtensions(): void
    {
        ModuleExtensions::registerReceiptModule('invoice-receipt');
        ModuleExtensions::registerSalesTemplate('invoice-receipt', 'invoice_receipt');

        ModuleExtensions::registerSerialNumberType('invoice_receipt', Invoice::class, [
            'type' => Invoice::TYPE_INVOICE,
            'template_name' => 'invoice_receipt',
        ]);

        // HisabKitab feature — hide core Invoices and Estimates when this module
        // is enabled, since it replaces them with Invoice Receipts.
        ModuleExtensions::registerMenuFilter('invoice-receipt', function (array $menu): array {
            return array_values(array_filter($menu, function ($item) {
                return ! in_array($item['name'] ?? '', ['Invoices', 'Estimates']);
            }));
        });

        ModuleExtensions::registerDashboardCountProvider('invoice-receipt', function (int $companyId): array {
            $count = Invoice::query()
                ->where('company_id', $companyId)
                ->where('type', Invoice::TYPE_INVOICE)
                ->where('template_name', 'invoice_receipt')
                ->count();

            return [[
                'key' => 'invoice_receipt',
                'label' => $count === 1 ? 'Invoice Receipt' : 'Invoice Receipts',
                'to' => '/admin/invoices?view=invoice_receipt',
                'value' => $count,
            ]];
        });

        // Invoice Receipt exposes tr_ fields for the edit form
        ModuleExtensions::registerInvoiceResourceFields('invoice-receipt', function ($invoice): array {
            // HisabKitab feature — GST Tax Payable By resolver: checks tr_ columns
            // first, then falls back to custom fields. Moved here from the host's
            // Invoice model accessor so the host stays module-agnostic.
            $gstPayableBy = null;
            if (! empty($invoice->attributes['tr_gst_payable_by'])) {
                $gstPayableBy = $invoice->attributes['tr_gst_payable_by'];
            } elseif (! empty($invoice->attributes['tr_gst_through'])) {
                $gstPayableBy = $invoice->attributes['tr_gst_through'];
            } else {
                $fields = $invoice->relationLoaded('fields') ? $invoice->fields : $invoice->fields()->with('customField')->get();
                $field = $fields->first(function ($f) {
                    $slug = $f->customField?->slug ?? '';
                    $name = strtolower($f->customField?->name ?? '');

                    return in_array($slug, ['CUSTOM_Invoice_GST_TAX_PAYABLE_BY', 'CUSTOM_Invoice_GST_TAX_THROUGH'])
                        || in_array($name, ['gst tax payable by', 'gst tax through', 'gst payable by']);
                });

                if ($field && ! empty($field->value)) {
                    $gstPayableBy = is_array($field->value) ? ($field->value['name'] ?? null) : (string) $field->value;
                }
            }

            return [
                'gst_tax_payable_by' => $gstPayableBy,
                'tr_gst_through' => $invoice->tr_gst_through,
                'tr_from_name' => $invoice->tr_from_name,
                'tr_from_code' => $invoice->tr_from_code,
                'tr_to_name' => $invoice->tr_to_name,
                'tr_to_code' => $invoice->tr_to_code,
                'tr_truck_no' => $invoice->tr_truck_no,
                'tr_mode_of_payment' => $invoice->tr_mode_of_payment,
                'tr_time' => $invoice->tr_time,
                'tr_consignor' => $invoice->tr_consignor,
                'tr_consignor_phone' => $invoice->tr_consignor_phone,
                'tr_consignor_gst' => $invoice->tr_consignor_gst,
                'tr_consignee' => $invoice->tr_consignee,
                'tr_consignee_phone' => $invoice->tr_consignee_phone,
                'tr_consignee_gst' => $invoice->tr_consignee_gst,
                'tr_description_goods' => $invoice->tr_description_goods,
                'tr_hsn_code' => $invoice->tr_hsn_code,
                'tr_delivery_at' => $invoice->tr_delivery_at,
                'tr_eway_bill_no' => $invoice->tr_eway_bill_no,
                'tr_no_of_articles' => $invoice->tr_no_of_articles,
                'tr_packing' => $invoice->tr_packing,
                'tr_actual_weight' => $invoice->tr_actual_weight,
                'tr_charged_weight' => $invoice->tr_charged_weight,
                'tr_goods_value' => $invoice->tr_goods_value,
                'tr_pod_required' => $invoice->tr_pod_required,
                'tr_basic_freight' => $invoice->tr_basic_freight,
                'tr_hamali' => $invoice->tr_hamali,
                'tr_fov' => $invoice->tr_fov,
                'tr_local_collection' => $invoice->tr_local_collection,
                'tr_door_delivery' => $invoice->tr_door_delivery,
                'tr_docket_charge' => $invoice->tr_docket_charge,
                'tr_other_charge' => $invoice->tr_other_charge,
                'tr_net_amount' => $invoice->tr_net_amount,
            ];
        });

        // HisabKitab feature — Invoice Receipt per-item consignment fields.
        // Stored in tr_ prefixed columns, exposed with the same tr_ names
        // the edit form uses so loading and saving use the same key.
        ModuleExtensions::registerInvoiceItemResourceFields('invoice-receipt', function ($item): array {
            return [
                'tr_consignment_number' => $item->tr_consignment_number,
                'tr_consignment_date' => $item->tr_consignment_date,
                'tr_party_inv_no' => $item->tr_party_inv_no,
                'tr_from_name' => $item->tr_from_name,
                'tr_to_name' => $item->tr_to_name,
                'tr_truck_no' => $item->tr_truck_no,
                'tr_pkg_weight' => $item->tr_pkg_weight,
                'tr_charged_weight' => $item->tr_charged_weight,
                'tr_rate' => $item->tr_rate,
                'tr_other_charge' => $item->tr_other_charge,
                'tr_lr_charge' => $item->tr_lr_charge,
                'tr_dd_charge' => $item->tr_dd_charge,
                'amount' => $item->tr_rate,
            ];
        });
    }

    /**
     * Copy the PDF blade template to the pdf_templates custom disk so the
     * host's PdfTemplateUtils::resolveView() can find it.
     */
    private static function publishPdfTemplate(string $modulePath): void
    {
        $source = $modulePath.'/resources/views/pdf/invoice_receipt.blade.php';
        $dest = storage_path('app/templates/pdf/invoice/invoice_receipt.blade.php');

        if (! file_exists($dest) && file_exists($source)) {
            @mkdir(dirname($dest), 0755, true);
            @copy($source, $dest);
        }
    }
}
