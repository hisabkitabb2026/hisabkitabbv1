<?php

declare(strict_types=1);

namespace Modules\LorryReceipt\Lifecycle;

use InvoiceShelf\Modules\Contracts\DataCleanup;
use InvoiceShelf\Modules\Contracts\Host\SettingsStore;

class DataCleanup implements DataCleanup
{
    public function __construct(
        private readonly SettingsStore $settings,
    ) {}

    public function cleanup(): void
    {
        $pdfTemplate = storage_path('app/pdf_templates/invoice/lorry_receipt.blade.php');

        if (file_exists($pdfTemplate)) {
            @unlink($pdfTemplate);
        }
    }
}
