<?php

declare(strict_types=1);

namespace Modules\Quotation\Lifecycle;

use InvoiceShelf\Modules\Contracts\DataCleanup as DataCleanupContract;
use InvoiceShelf\Modules\Contracts\Host\SettingsStore;

/**
 * Cleanup logic run when the module is uninstalled.
 * Removes the published PDF template from the pdf_templates disk.
 */
class DataCleanup implements DataCleanupContract
{
    public function __construct(
        private readonly SettingsStore $settings,
    ) {}

    public function cleanup(): void
    {
        $pdfTemplate = storage_path('app/templates/pdf/estimate/quotation.blade.php');

        if (file_exists($pdfTemplate)) {
            @unlink($pdfTemplate);
        }
    }
}
