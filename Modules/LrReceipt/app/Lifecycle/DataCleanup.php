<?php

declare(strict_types=1);

namespace Modules\LrReceipt\Lifecycle;

use InvoiceShelf\Modules\Contracts\DataCleanup;
use InvoiceShelf\Modules\Contracts\Host\SettingsStore;

/**
 * Cleanup logic run when the module is uninstalled.
 * Removes the published PDF template from the pdf_templates disk.
 */
class DataCleanup implements DataCleanup
{
    public function __construct(
        private readonly SettingsStore $settings,
    ) {}

    public function cleanup(): void
    {
        $pdfTemplate = storage_path('app/pdf_templates/invoice/lr_receipt.blade.php');

        if (file_exists($pdfTemplate)) {
            @unlink($pdfTemplate);
        }
    }
}
