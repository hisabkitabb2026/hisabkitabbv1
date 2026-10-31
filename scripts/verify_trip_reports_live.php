<?php

declare(strict_types=1);

use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\CompanySetting;
use App\Domains\Money\Models\Currency;
use App\Platform\Pdf\Facades\Pdf;
use App\Platform\Pdf\Rendering\PdfPageSetup;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Modules\Trips\Application\TripReportService;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$companyId = 1;
$company = Company::find($companyId);
$companyName = $company?->name ?: 'Company 1';

echo "=========================================================================================\n";
echo "       HISABKITABB - TRIP REPORTS LIVE VERIFICATION FOR: {$companyName}\n";
echo "=========================================================================================\n\n";

$reportsService = app(TripReportService::class);

$from = Carbon::now()->startOfYear();
$to = Carbon::now();
$filters = [];

$summary = $reportsService->summary(
    $companyId,
    $from->toDateString(),
    $to->toDateString(),
    $filters
);

$currencyId = $company ? (int) CompanySetting::getSetting('currency', $company->id) : 0;
$currency = $currencyId > 0 ? Currency::find($currencyId) : null;
if (! $currency) {
    $currency = Currency::first();
}

$dateFormat = $company ? (string) CompanySetting::getSetting('carbon_date_format', $company->id) : 'Y-m-d';
if (empty($dateFormat)) {
    $dateFormat = 'd M, Y';
}

$reportTypes = [
    'customer' => 'By Customer Name',
    'lr' => 'By LR / Consignment',
    'supplier' => 'By Truck Owner / Supplier',
    'invoice' => 'By Invoice Receipt',
    'details' => 'All Trip Details (The Money)',
];

printf("%-32s | %-12s | %-16s | %s\n", 'Report Type', 'Data Rows', 'PDF File Size', 'Status');
echo str_repeat('-', 80)."\n";

foreach ($reportTypes as $type => $label) {
    $rows = match ($type) {
        'lr' => $reportsService->byLr($companyId, $from->toDateString(), $to->toDateString(), $filters),
        'supplier' => $reportsService->bySupplier($companyId, $from->toDateString(), $to->toDateString(), $filters),
        'invoice' => $reportsService->byInvoice($companyId, $from->toDateString(), $to->toDateString(), $filters),
        'details' => [],
        default => $summary['by_customer'],
    };

    $tripDetails = $reportsService->tripDetails(
        $companyId,
        $from->toDateString(),
        $to->toDateString(),
        $filters
    );

    view()->share([
        'company' => $company,
        'logo' => $company?->logo_path,
        'from_date' => $from->translatedFormat($dateFormat),
        'to_date' => $to->translatedFormat($dateFormat),
        'currency' => $currency,
        'type' => $type,
        'rows' => $rows,
        'totals' => $summary['totals'],
        'trip_details' => $tripDetails,
        'filters' => $filters,
    ]);

    try {
        $pdf = Pdf::loadView('trips::trip-report', [], PdfPageSetup::forReports());
        $output = $pdf->output();
        $sizeKb = round(strlen($output) / 1024, 1).' KB';
        $rowCount = ($type === 'details') ? count($tripDetails) : count($rows);

        printf("%-32s | %-12s | %-16s | %s\n", $label, "{$rowCount} rows", $sizeKb, '✅ OK (Generated)');
    } catch (Throwable $e) {
        printf("%-32s | %-12s | %-16s | %s\n", $label, 'Error', '0 KB', '❌ FAILED: '.$e->getMessage());
    }
}

echo str_repeat('-', 80)."\n";
echo "Summary Totals in Generated Reports:\n";
echo '  • Total Trips:   '.$summary['totals']['trip_count']."\n";
echo '  • Total Revenue: ₹'.number_format($summary['totals']['revenue'] / 100, 2)."\n";
echo '  • Total Cost:    ₹'.number_format($summary['totals']['cost'] / 100, 2)."\n";
echo '  • Total Profit:  ₹'.number_format($summary['totals']['profit'] / 100, 2)."\n";
echo "=========================================================================================\n";
