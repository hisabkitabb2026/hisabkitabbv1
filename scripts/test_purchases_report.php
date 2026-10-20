<?php

declare(strict_types=1);

use App\Domains\Reporting\Queries\PurchasesQuery;
use App\Platform\Pdf\Rendering\PdfTemplateUtils;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\View;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$companyId = 1;
$from = $argv[1] ?? '2026-10-01';
$to = $argv[2] ?? '2026-10-31';

echo "Testing Purchases Report for Company {$companyId} from {$from} to {$to}...\n";

// 1. Check resolved view
$resolvedView = PdfTemplateUtils::resolveView('reports', 'purchases');
echo "Resolved PDF View: {$resolvedView}\n";

$viewPath = View::getFinder()->find($resolvedView);
echo "View File Path: {$viewPath}\n";

// Check if view file contains lorry_receipts
$viewContent = file_get_contents($viewPath);
$hasLrSection = str_contains($viewContent, 'Lorry Receipt Transport Details');
echo "View Contains 'Lorry Receipt Transport Details': ".($hasLrSection ? 'YES' : 'NO')."\n\n";

// 2. Test lorryReceipts query
$purchasesQuery = app(PurchasesQuery::class);
$lrData = $purchasesQuery->lorryReceipts($companyId, $from, $to);

echo 'Found '.count($lrData['rows'])." Lorry Receipt row(s) in this date range.\n";
echo "Totals:\n";
print_r($lrData['totals']);

echo "\nRows:\n";
foreach ($lrData['rows'] as $row) {
    echo "  - Challan {$row['challan_no']} | LR: {$row['lr_no']} | Lorry: {$row['lorry_no']} | Paid To: {$row['paid_to']} | Hire: Rs.".($row['lorry_hire'] / 100).' | Adv: Rs.'.($row['advance_paid'] / 100).' | Rem: Rs.'.($row['remaining_paid'] / 100)."\n";
}
