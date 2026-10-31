@extends('app.pdf.reports.partials.layout')

@section('report-title', 'TRIP REPORT')
@section('footer-label', 'NET TRIP PROFIT')
@section('footer-value'){!! format_money_pdf($totals['profit'] ?? 0, $currency) !!}@endsection

@section('report-body')
    <style>
        .trip-card-block {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            margin-bottom: 14px;
        }
        .trip-card-table {
            width: 100%;
            border: 1px solid #CBD5E1;
            border-collapse: collapse;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .trip-card-table tr,
        .trip-card-table td {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
    </style>

    @if (! empty($filters))
        <p class="report-muted" style="margin-bottom: 8px;">
            @foreach ($filters as $key => $val)
                <span style="display:inline-block; margin-right: 12px;"><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $val }}</span>
            @endforeach
        </p>
    @endif

    {{-- Executive Summary KPI Table --}}
    <div class="report-section" style="padding-top: 4px; margin-bottom: 16px;">
        <table class="report-table" style="background: #F9FBFF; border: 0.62px solid #E8E8E8;">
            <thead>
                <tr>
                    <th style="padding: 8px 12px; text-align: center;">TOTAL TRIPS</th>
                    <th style="padding: 8px 12px; text-align: right;">TOTAL BILLED REVENUE</th>
                    <th style="padding: 8px 12px; text-align: right;">TRUCK HIRE &amp; COSTS</th>
                    <th style="padding: 8px 12px; text-align: right;">NET PROFIT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 8px 12px; text-align: center; font-size: 14px; font-weight: bold; color: #040405;">
                        {{ $totals['trip_count'] ?? 0 }}
                    </td>
                    <td style="padding: 8px 12px; text-align: right; font-size: 13px; font-weight: bold; color: #040405;">
                        {!! format_money_pdf($totals['revenue'] ?? 0, $currency) !!}
                    </td>
                    <td style="padding: 8px 12px; text-align: right; font-size: 13px; font-weight: bold; color: #dc2626;">
                        {!! format_money_pdf($totals['cost'] ?? 0, $currency) !!}
                    </td>
                    <td style="padding: 8px 12px; text-align: right; font-size: 14px; font-weight: bold; color: {{ ($totals['profit'] ?? 0) >= 0 ? '#16a34a' : '#dc2626' }};">
                        {!! format_money_pdf($totals['profit'] ?? 0, $currency) !!}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Individual Trip Cards with Page Break Protection --}}
    <div style="padding-top: 8px;">
        @forelse ($trip_details as $td)
            <div class="trip-card-block">
                <table class="trip-card-table">
                    <tbody>
                        <!-- Centered Bold Trip Header -->
                        <tr style="background: #F1F5F9; border-bottom: 1px solid #CBD5E1;">
                            <td colspan="5" style="text-align: center; padding: 6px 10px; font-weight: bold; font-size: 12px; color: #0F172A; letter-spacing: 0.05em;">
                                TRIP #{{ $td['trip_no'] }}
                                @if (!empty($td['lr_numbers']) && $td['lr_numbers'] !== '—')
                                    <span style="font-size: 11px; font-weight: normal; color: #475569;">[LR: {{ $td['lr_numbers'] }}]</span>
                                @endif
                            </td>
                        </tr>
                        <!-- Row 1: Operations & Movement (5 Columns) -->
                        <tr style="border-bottom: 0.62px solid #E2E8F0; background: #FFFFFF;">
                            <td style="width: 17%; padding: 6px 8px; font-size: 10px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 8px; text-transform: uppercase; display: block; font-weight: 600;">DISPATCH DATE</span>
                                <strong style="color: #0F172A;">{{ $td['pickup_date'] }}</strong>
                            </td>
                            <td style="width: 23%; padding: 6px 8px; font-size: 10px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 8px; text-transform: uppercase; display: block; font-weight: 600;">ROUTE</span>
                                <strong style="color: #0F172A;">{{ $td['from_city'] }} &rarr; {{ $td['to_city'] }}</strong>
                            </td>
                            <td style="width: 22%; padding: 6px 8px; font-size: 10px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 8px; text-transform: uppercase; display: block; font-weight: 600;">CUSTOMER NAME</span>
                                <strong style="color: #0F172A;">{{ $td['customer_name'] }}</strong>
                            </td>
                            <td style="width: 22%; padding: 6px 8px; font-size: 10px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 8px; text-transform: uppercase; display: block; font-weight: 600;">TRUCK NO &amp; OWNER</span>
                                <strong style="color: #0F172A;">{{ $td['truck_owner_display'] }}</strong>
                            </td>
                            <td style="width: 16%; padding: 6px 8px; font-size: 10px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 8px; text-transform: uppercase; display: block; font-weight: 600;">LR / BILTY NO(S)</span>
                                <strong style="color: #334155;">{{ $td['lr_numbers'] }}</strong>
                            </td>
                        </tr>
                        <!-- Row 2: Invoicing & Financial Settlement (5 Columns) -->
                        <tr style="background: #F8FAFC;">
                            <td style="width: 17%; padding: 6px 8px; font-size: 10px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 8px; text-transform: uppercase; display: block; font-weight: 600;">LORRY CHALLAN NO</span>
                                <span style="color: #334155; font-weight: 600;">{{ $td['lorry_receipt_numbers'] }}</span>
                            </td>
                            <td style="width: 23%; padding: 6px 8px; font-size: 10px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 8px; text-transform: uppercase; display: block; font-weight: 600;">INVOICE RECEIPT NO</span>
                                <span style="color: #334155; font-weight: 600;">{{ $td['invoice_numbers'] }}</span>
                            </td>
                            <td style="width: 22%; padding: 6px 8px; font-size: 10px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 8px; text-transform: uppercase; display: block; font-weight: 600;">CUSTOMER FREIGHT (IN)</span>
                                <strong style="color: #0F172A; font-size: 10px;">{!! format_money_pdf($td['revenue'], $currency) !!}</strong>
                            </td>
                            <td style="width: 22%; padding: 6px 8px; font-size: 10px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 8px; text-transform: uppercase; display: block; font-weight: 600;">TRUCK HIRE (OUT)</span>
                                <strong style="color: #DC2626; font-size: 10px;">{!! format_money_pdf($td['cost'], $currency) !!}</strong>
                            </td>
                            <td style="width: 16%; padding: 6px 8px; font-size: 10px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 8px; text-transform: uppercase; display: block; font-weight: 600;">NET TRIP PROFIT</span>
                                <strong style="color: {{ $td['profit'] >= 0 ? '#16A34A' : '#DC2626' }}; font-size: 10px;">
                                    {!! format_money_pdf($td['profit'], $currency) !!}
                                </strong>
                                @if ($td['revenue'] > 0)
                                    <span style="font-size: 8px; color: #64748B; display: block;">({{ $td['margin'] }}%)</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @empty
            <p class="report-muted">No records found for the selected period.</p>
        @endforelse
    </div>
@endsection
