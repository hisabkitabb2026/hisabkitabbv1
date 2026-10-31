<?php

declare(strict_types=1);

namespace Modules\Trips\Http\Controllers;

use App\Domains\Accounts\Models\Company;
use App\Domains\Accounts\Models\CompanySetting;
use App\Domains\Money\Models\Currency;
use App\Platform\Pdf\Facades\Pdf;
use App\Platform\Pdf\Rendering\PdfPageSetup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Trips\Application\TripReportService;
use Modules\Trips\Http\Requests\ReportSummaryRequest;
use Modules\Trips\Support\Abilities;
use Modules\Trips\Support\Authorizes;

/**
 * Read-only aggregates over trips, for the reports page.
 *
 * The range defaults to the current month.
 */
final class TripReportsController extends Controller
{
    public function __construct(
        Authorizes $authorizes,
        private readonly TripReportService $reports,
    ) {
        parent::__construct($authorizes);
    }

    public function summary(ReportSummaryRequest $request): JsonResponse
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TRIP);

        $filters = $request->validated();
        $from = isset($filters['from']) ? Carbon::parse($filters['from']) : Carbon::now()->startOfMonth();
        $to = isset($filters['to']) ? Carbon::parse($filters['to']) : Carbon::now();

        return response()->json(['data' => $this->reports->summary(
            $context->companyId,
            $from->toDateString(),
            $to->toDateString(),
        )]);
    }

    /**
     * Render the trip report as a PDF and stream it back as a blob.
     *
     * The route sits behind the same company/bouncer middleware as every other
     * trip endpoint, so the company is already on the request — no hash needed,
     * unlike the host's financial reports.
     */
    public function pdf(Request $request)
    {
        $context = $this->context($request);
        $this->authorize($context, Abilities::VIEW_TRIP);

        $from = $request->date('from_date') ?? Carbon::now()->startOfMonth();
        $to = $request->date('to_date') ?? Carbon::now();
        $type = $request->string('type', 'customer')->toString();

        $filters = [
            'customer' => $request->string('customer', '')->toString(),
            'lr_no' => $request->string('lr_no', '')->toString(),
            'invoice_no' => $request->string('invoice_no', '')->toString(),
            'supplier' => $request->string('supplier', '')->toString(),
        ];

        $filters = array_filter($filters, fn ($v) => trim($v) !== '');

        $summary = $this->reports->summary(
            $context->companyId,
            $from->toDateString(),
            $to->toDateString(),
            $filters,
        );

        $tripDetails = $this->reports->tripDetails(
            $context->companyId,
            $from->toDateString(),
            $to->toDateString(),
            $filters,
        );

        $tripsByCustomer = [];
        foreach ($tripDetails as $td) {
            $cust = $td['receivable'][0]['customer_name'] ?? 'Other / Direct Customer';
            if (! isset($tripsByCustomer[$cust])) {
                $tripsByCustomer[$cust] = [
                    'customer_name' => $cust,
                    'trips' => [],
                    'total_revenue' => 0,
                    'total_cost' => 0,
                    'total_profit' => 0,
                ];
            }
            $tripsByCustomer[$cust]['trips'][] = $td;
            $tripsByCustomer[$cust]['total_revenue'] += $td['revenue'];
            $tripsByCustomer[$cust]['total_cost'] += $td['cost'];
            $tripsByCustomer[$cust]['total_profit'] += $td['profit'];
        }
        ksort($tripsByCustomer);

        $company = Company::query()->find($context->companyId);
        $currencyId = $company ? (int) CompanySetting::getSetting('currency', $company->id) : 0;
        $currency = $currencyId > 0 ? Currency::find($currencyId) : null;
        if (! $currency) {
            $currency = Currency::first();
        }

        $dateFormat = $company ? (string) CompanySetting::getSetting('carbon_date_format', $company->id) : 'Y-m-d';
        if (empty($dateFormat)) {
            $dateFormat = 'd M, Y';
        }

        view()->share([
            'company' => $company,
            'logo' => $company?->logo_path,
            'from_date' => $from->translatedFormat($dateFormat),
            'to_date' => $to->translatedFormat($dateFormat),
            'currency' => $currency,
            'type' => $type,
            'trips_by_customer' => $tripsByCustomer,
            'totals' => $summary['totals'],
            'trip_details' => $tripDetails,
            'filters' => $filters,
        ]);

        $pdf = Pdf::loadView('trips::trip-report', [], PdfPageSetup::forReports());

        return $request->exists('download')
            ? $pdf->download('trip-report.pdf')
            : $pdf->stream();
    }
}
