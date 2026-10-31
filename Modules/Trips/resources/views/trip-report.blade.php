@extends('app.pdf.reports.partials.layout')

@php
    $reportTitle = ($type === 'customer') ? 'TRIP REPORT — BY CUSTOMER' : 'TRIP REPORT — BY LR';
@endphp

@section('report-title', $reportTitle)
@section('footer-label', 'NET TRIP PROFIT')
@section('footer-value'){!! format_money_pdf($totals['profit'] ?? 0, $currency) !!}@endsection

@section('report-body')
    @if (! empty($filters))
        <p class="report-muted" style="margin-bottom: 8px;">
            @foreach ($filters as $key => $val)
                <span style="display:inline-block; margin-right: 12px;"><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $val }}</span>
            @endforeach
        </p>
    @endif

    {{-- Executive Summary KPI Table --}}
    <div class="report-section" style="padding-top: 4px;">
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
                        {{ $totals['trip_count'] }}
                    </td>
                    <td style="padding: 8px 12px; text-align: right; font-size: 13px; font-weight: bold; color: #040405;">
                        {!! format_money_pdf($totals['revenue'], $currency) !!}
                    </td>
                    <td style="padding: 8px 12px; text-align: right; font-size: 13px; font-weight: bold; color: #dc2626;">
                        {!! format_money_pdf($totals['cost'], $currency) !!}
                    </td>
                    <td style="padding: 8px 12px; text-align: right; font-size: 14px; font-weight: bold; color: {{ ($totals['profit'] ?? 0) >= 0 ? '#16a34a' : '#dc2626' }};">
                        {!! format_money_pdf($totals['profit'], $currency) !!}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if ($type === 'customer')
        {{-- GROUPED BY CUSTOMER --}}
        @forelse ($trips_by_customer as $custGroup)
            <div class="report-section" style="page-break-inside: avoid; margin-bottom: 22px;">
                <p class="report-section-heading" style="font-size: 13px; font-weight: bold; color: #1E293B; border-bottom: 1.5px solid #CBD5E1; padding-bottom: 4px; margin-bottom: 10px;">
                    {{ $custGroup['customer_name'] }}
                </p>

                @foreach ($custGroup['trips'] as $td)
                    @php
                        $customerName = $td['receivable'][0]['customer_name'] ?? '—';
                        $supplierName = $td['payable'][0]['supplier_name'] ?? ($td['lorry_no'] !== '—' ? $td['lorry_no'] : '—');
                    @endphp
                    <table class="trip-card-table" style="width: 100%; border: 1px solid #CBD5E1; margin-bottom: 12px; border-collapse: collapse; page-break-inside: avoid;">
                        <!-- Centered Bold Trip Header -->
                        <tr style="background: #F1F5F9; border-bottom: 1px solid #CBD5E1;">
                            <td colspan="3" style="text-align: center; padding: 6px; font-weight: bold; font-size: 12px; color: #0F172A; letter-spacing: 0.05em;">
                                TRIP #{{ $td['trip_no'] }}
                                @if (!empty($td['lr_numbers']) && $td['lr_numbers'] !== '—')
                                    <span style="font-size: 11px; font-weight: normal; color: #475569;">[LR: {{ $td['lr_numbers'] }}]</span>
                                @endif
                            </td>
                        </tr>
                        <!-- Row 1: Journey & Parties -->
                        <tr style="border-bottom: 0.62px solid #E2E8F0; background: #FFFFFF;">
                            <td style="width: 33.33%; padding: 6px 10px; font-size: 11px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">ROUTE</span>
                                <strong style="color: #0F172A;">{{ $td['from_city'] }} &rarr; {{ $td['to_city'] }}</strong>
                            </td>
                            <td style="width: 33.33%; padding: 6px 10px; font-size: 11px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">CUSTOMER</span>
                                <strong style="color: #0F172A;">{{ $customerName }}</strong>
                            </td>
                            <td style="width: 33.33%; padding: 6px 10px; font-size: 11px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">VEHICLE &amp; TRANSPORTER</span>
                                <strong style="color: #0F172A;">{{ $td['lorry_no'] !== '—' ? $td['lorry_no'] : '' }} {{ $supplierName !== '—' ? '('.$supplierName.')' : '—' }}</strong>
                            </td>
                        </tr>
                        <!-- Row 2: Transport Documents -->
                        <tr style="border-bottom: 0.62px solid #E2E8F0; background: #F8FAFC;">
                            <td style="padding: 6px 10px; font-size: 11px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">LR / CONSIGNMENT NO</span>
                                <span style="color: #334155; font-weight: 600;">{{ $td['lr_numbers'] }}</span>
                            </td>
                            <td style="padding: 6px 10px; font-size: 11px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">LORRY RECEIPT (CHALLAN)</span>
                                <span style="color: #334155; font-weight: 600;">{{ $td['lorry_receipt_numbers'] }}</span>
                            </td>
                            <td style="padding: 6px 10px; font-size: 11px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">INVOICE RECEIPT NO</span>
                                <span style="color: #334155; font-weight: 600;">{{ $td['invoice_numbers'] }}</span>
                            </td>
                        </tr>
                        <!-- Row 3: Financial Settlement -->
                        <tr style="background: #FFFFFF;">
                            <td style="padding: 6px 10px; font-size: 11px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">CUSTOMER FREIGHT (REVENUE)</span>
                                <strong style="color: #0F172A; font-size: 11px;">{!! format_money_pdf($td['revenue'], $currency) !!}</strong>
                            </td>
                            <td style="padding: 6px 10px; font-size: 11px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">TRUCK HIRE (COST)</span>
                                <strong style="color: #DC2626; font-size: 11px;">{!! format_money_pdf($td['cost'], $currency) !!}</strong>
                            </td>
                            <td style="padding: 6px 10px; font-size: 11px; vertical-align: top;">
                                <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">NET TRIP PROFIT</span>
                                <strong style="color: {{ $td['profit'] >= 0 ? '#16A34A' : '#DC2626' }}; font-size: 11px;">
                                    {!! format_money_pdf($td['profit'], $currency) !!}
                                </strong>
                            </td>
                        </tr>
                    </table>
                @endforeach

                <!-- Subtotal Table for Customer -->
                <table class="report-table" style="margin-top: 4px; margin-bottom: 12px; background: #F8FAFC; border: 0.62px solid #CBD5E1;">
                    <tr class="report-total-row">
                        <td class="report-total" style="padding: 6px 10px;">Total for {{ $custGroup['customer_name'] }} ({{ count($custGroup['trips']) }} {{ count($custGroup['trips']) === 1 ? 'Trip' : 'Trips' }})</td>
                        <td class="report-amount report-total" style="padding: 6px 10px;">{!! format_money_pdf($custGroup['total_revenue'], $currency) !!}</td>
                        <td class="report-amount report-total" style="padding: 6px 10px; color: #DC2626;">{!! format_money_pdf($custGroup['total_cost'], $currency) !!}</td>
                        <td class="report-amount report-total" style="padding: 6px 10px; color: {{ $custGroup['total_profit'] >= 0 ? '#16A34A' : '#DC2626' }};">
                            {!! format_money_pdf($custGroup['total_profit'], $currency) !!}
                        </td>
                    </tr>
                </table>
            </div>
        @empty
            <div class="report-section">
                <p class="report-muted">No records found for the selected period.</p>
            </div>
        @endforelse

    @else
        {{-- BY LR / CONSIGNMENT --}}
        <div class="report-section">
            @forelse ($trip_details as $td)
                @php
                    $customerName = $td['receivable'][0]['customer_name'] ?? '—';
                    $supplierName = $td['payable'][0]['supplier_name'] ?? ($td['lorry_no'] !== '—' ? $td['lorry_no'] : '—');
                @endphp
                <table class="trip-card-table" style="width: 100%; border: 1px solid #CBD5E1; margin-bottom: 14px; border-collapse: collapse; page-break-inside: avoid;">
                    <!-- Centered Bold Trip Header -->
                    <tr style="background: #F1F5F9; border-bottom: 1px solid #CBD5E1;">
                        <td colspan="3" style="text-align: center; padding: 6px; font-weight: bold; font-size: 12px; color: #0F172A; letter-spacing: 0.05em;">
                            TRIP #{{ $td['trip_no'] }}
                            @if (!empty($td['lr_numbers']) && $td['lr_numbers'] !== '—')
                                <span style="font-size: 11px; font-weight: normal; color: #475569;">[LR: {{ $td['lr_numbers'] }}]</span>
                            @endif
                        </td>
                    </tr>
                    <!-- Row 1: Journey & Parties -->
                    <tr style="border-bottom: 0.62px solid #E2E8F0; background: #FFFFFF;">
                        <td style="width: 33.33%; padding: 6px 10px; font-size: 11px; vertical-align: top;">
                            <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">ROUTE</span>
                            <strong style="color: #0F172A;">{{ $td['from_city'] }} &rarr; {{ $td['to_city'] }}</strong>
                        </td>
                        <td style="width: 33.33%; padding: 6px 10px; font-size: 11px; vertical-align: top;">
                            <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">CUSTOMER</span>
                            <strong style="color: #0F172A;">{{ $customerName }}</strong>
                        </td>
                        <td style="width: 33.33%; padding: 6px 10px; font-size: 11px; vertical-align: top;">
                            <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">VEHICLE &amp; TRANSPORTER</span>
                            <strong style="color: #0F172A;">{{ $td['lorry_no'] !== '—' ? $td['lorry_no'] : '' }} {{ $supplierName !== '—' ? '('.$supplierName.')' : '—' }}</strong>
                        </td>
                    </tr>
                    <!-- Row 2: Transport Documents -->
                    <tr style="border-bottom: 0.62px solid #E2E8F0; background: #F8FAFC;">
                        <td style="padding: 6px 10px; font-size: 11px; vertical-align: top;">
                            <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">LR / CONSIGNMENT NO</span>
                            <span style="color: #334155; font-weight: 600;">{{ $td['lr_numbers'] }}</span>
                        </td>
                        <td style="padding: 6px 10px; font-size: 11px; vertical-align: top;">
                            <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">LORRY RECEIPT (CHALLAN)</span>
                            <span style="color: #334155; font-weight: 600;">{{ $td['lorry_receipt_numbers'] }}</span>
                        </td>
                        <td style="padding: 6px 10px; font-size: 11px; vertical-align: top;">
                            <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">INVOICE RECEIPT NO</span>
                            <span style="color: #334155; font-weight: 600;">{{ $td['invoice_numbers'] }}</span>
                        </td>
                    </tr>
                    <!-- Row 3: Financial Settlement -->
                    <tr style="background: #FFFFFF;">
                        <td style="padding: 6px 10px; font-size: 11px; vertical-align: top;">
                            <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">CUSTOMER FREIGHT (REVENUE)</span>
                            <strong style="color: #0F172A; font-size: 11px;">{!! format_money_pdf($td['revenue'], $currency) !!}</strong>
                        </td>
                        <td style="padding: 6px 10px; font-size: 11px; vertical-align: top;">
                            <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">TRUCK HIRE (COST)</span>
                            <strong style="color: #DC2626; font-size: 11px;">{!! format_money_pdf($td['cost'], $currency) !!}</strong>
                        </td>
                        <td style="padding: 6px 10px; font-size: 11px; vertical-align: top;">
                            <span style="color: #64748B; font-size: 9px; text-transform: uppercase; display: block; font-weight: 600;">NET TRIP PROFIT</span>
                            <strong style="color: {{ $td['profit'] >= 0 ? '#16A34A' : '#DC2626' }}; font-size: 11px;">
                                {!! format_money_pdf($td['profit'], $currency) !!}
                            </strong>
                        </td>
                    </tr>
                </table>
            @empty
                <p class="report-muted">No records found for the selected period.</p>
            @endforelse
        </div>
    @endif
@endsection