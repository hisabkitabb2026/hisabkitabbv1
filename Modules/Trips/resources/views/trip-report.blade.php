@extends('app.pdf.reports.partials.layout')

@php
    $reportTitles = [
        'customer' => 'TRIP REPORT — BY CUSTOMER',
        'lr' => 'TRIP REPORT — BY LR / CONSIGNMENT',
        'supplier' => 'TRIP REPORT — BY TRUCK OWNER / SUPPLIER',
        'invoice' => 'TRIP REPORT — BY INVOICE RECEIPT',
        'details' => 'TRIP REPORT — ALL TRIP DETAILS',
    ];
    $title = $reportTitles[$type] ?? 'TRIP REPORT';
@endphp

@section('report-title', $title)
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
        <div class="report-section">
            <p class="report-section-heading">Customer Performance Summary</p>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th style="text-align: center; width: 14%;">Trips</th>
                        <th class="report-amount report-col-amount">Revenue</th>
                        <th class="report-amount report-col-amount">Cost</th>
                        <th class="report-amount report-col-amount">Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td style="font-weight: 500;">{{ $row['customer_name'] }}</td>
                            <td style="text-align: center;">{{ $row['trip_count'] }}</td>
                            <td class="report-amount">{!! format_money_pdf($row['revenue'], $currency) !!}</td>
                            <td class="report-amount">{!! format_money_pdf($row['cost'], $currency) !!}</td>
                            <td class="report-amount" style="color: {{ $row['profit'] >= 0 ? '#16a34a' : '#dc2626' }}; font-weight: 600;">
                                {!! format_money_pdf($row['profit'], $currency) !!}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="report-muted">No records found for the selected period.</td>
                        </tr>
                    @endforelse
                    <tr class="report-total-row">
                        <td class="report-total">Total</td>
                        <td class="report-total" style="text-align: center;">{{ $totals['trip_count'] }}</td>
                        <td class="report-amount report-total">{!! format_money_pdf($totals['revenue'], $currency) !!}</td>
                        <td class="report-amount report-total">{!! format_money_pdf($totals['cost'], $currency) !!}</td>
                        <td class="report-amount report-total" style="color: {{ ($totals['profit'] ?? 0) >= 0 ? '#16a34a' : '#dc2626' }};">
                            {!! format_money_pdf($totals['profit'], $currency) !!}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @elseif ($type === 'lr')
        <div class="report-section">
            <p class="report-section-heading">Consignment Performance (By LR)</p>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 14%;">LR No</th>
                        <th>Customer</th>
                        <th>Route</th>
                        <th class="report-amount report-col-amount">Revenue</th>
                        <th class="report-amount report-col-amount">Cost</th>
                        <th class="report-amount report-col-amount">Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td style="font-weight: 600;">#{{ $row['lr_no'] ?? '—' }}</td>
                            <td>{{ $row['customer_name'] ?? '—' }}</td>
                            <td>{{ ($row['from_city'] ?? '—') . ' → ' . ($row['to_city'] ?? '—') }}</td>
                            <td class="report-amount">{!! format_money_pdf($row['revenue'], $currency) !!}</td>
                            <td class="report-amount">{!! format_money_pdf($row['cost'], $currency) !!}</td>
                            <td class="report-amount" style="color: {{ $row['profit'] >= 0 ? '#16a34a' : '#dc2626' }}; font-weight: 600;">
                                {!! format_money_pdf($row['profit'], $currency) !!}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="report-muted">No records found for the selected period.</td>
                        </tr>
                    @endforelse
                    <tr class="report-total-row">
                        <td class="report-total" colspan="3">Total</td>
                        <td class="report-amount report-total">{!! format_money_pdf($totals['revenue'], $currency) !!}</td>
                        <td class="report-amount report-total">{!! format_money_pdf($totals['cost'], $currency) !!}</td>
                        <td class="report-amount report-total" style="color: {{ ($totals['profit'] ?? 0) >= 0 ? '#16a34a' : '#dc2626' }};">
                            {!! format_money_pdf($totals['profit'], $currency) !!}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @elseif ($type === 'supplier')
        <div class="report-section">
            <p class="report-section-heading">Truck Owner / Transporter Breakdown</p>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Owner / Transporter</th>
                        <th>Bill No</th>
                        <th>Reference</th>
                        <th style="text-align: center; width: 10%;">Trips</th>
                        <th class="report-amount report-col-amount">Revenue</th>
                        <th class="report-amount report-col-amount">Cost</th>
                        <th class="report-amount report-col-amount">Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td style="font-weight: 600;">{{ $row['supplier_name'] }}</td>
                            <td>{{ $row['bill_number'] }}</td>
                            <td>{{ $row['bill_reference'] }}</td>
                            <td style="text-align: center;">{{ $row['trip_count'] }}</td>
                            <td class="report-amount">{!! format_money_pdf($row['revenue'], $currency) !!}</td>
                            <td class="report-amount">{!! format_money_pdf($row['cost'], $currency) !!}</td>
                            <td class="report-amount" style="color: {{ $row['profit'] >= 0 ? '#16a34a' : '#dc2626' }}; font-weight: 600;">
                                {!! format_money_pdf($row['profit'], $currency) !!}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="report-muted">No records found for the selected period.</td>
                        </tr>
                    @endforelse
                    <tr class="report-total-row">
                        <td class="report-total" colspan="3">Total</td>
                        <td class="report-total" style="text-align: center;">{{ $totals['trip_count'] }}</td>
                        <td class="report-amount report-total">{!! format_money_pdf($totals['revenue'], $currency) !!}</td>
                        <td class="report-amount report-total">{!! format_money_pdf($totals['cost'], $currency) !!}</td>
                        <td class="report-amount report-total" style="color: {{ ($totals['profit'] ?? 0) >= 0 ? '#16a34a' : '#dc2626' }};">
                            {!! format_money_pdf($totals['profit'], $currency) !!}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @elseif ($type === 'invoice')
        <div class="report-section">
            <p class="report-section-heading">Customer Tax Invoice Breakdown</p>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th class="report-amount report-col-amount">Total Billed</th>
                        <th class="report-amount report-col-amount">Due Amount</th>
                        <th style="text-align: center;">Status</th>
                        <th class="report-amount report-col-amount">Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td style="font-weight: 600;">{{ $row['invoice_number'] }}</td>
                            <td>{{ $row['customer_name'] }}</td>
                            <td class="report-amount">{!! format_money_pdf($row['total'], $currency) !!}</td>
                            <td class="report-amount">{!! format_money_pdf($row['due_amount'], $currency) !!}</td>
                            <td style="text-align: center;">{{ $row['paid_status'] }}</td>
                            <td class="report-amount" style="color: {{ $row['profit'] >= 0 ? '#16a34a' : '#dc2626' }}; font-weight: 600;">
                                {!! format_money_pdf($row['profit'], $currency) !!}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="report-muted">No records found for the selected period.</td>
                        </tr>
                    @endforelse
                    <tr class="report-total-row">
                        <td class="report-total" colspan="2">Total</td>
                        <td class="report-amount report-total">{!! format_money_pdf($totals['revenue'], $currency) !!}</td>
                        <td class="report-amount report-total" colspan="2"></td>
                        <td class="report-amount report-total" style="color: {{ ($totals['profit'] ?? 0) >= 0 ? '#16a34a' : '#dc2626' }};">
                            {!! format_money_pdf($totals['profit'], $currency) !!}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    {{-- Detailed Trip Ledger --}}
    @if ($type === 'details' || ! empty($trip_details))
        <div class="report-section">
            <p class="report-section-heading">Trip Details &amp; Document Chain</p>
            <table class="report-table" style="font-size: 11px;">
                <thead>
                    <tr>
                        <th style="width: 8%;">Trip #</th>
                        <th>Route</th>
                        <th>LR(s)</th>
                        <th>Truck / Challan</th>
                        <th>Customer</th>
                        <th>Transporter</th>
                        <th class="report-amount">Revenue</th>
                        <th class="report-amount">Cost</th>
                        <th class="report-amount">Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trip_details as $td)
                        @php
                            $customerName = $td['receivable'][0]['customer_name'] ?? '—';
                            $supplierName = $td['payable'][0]['supplier_name'] ?? '—';
                        @endphp
                        <tr>
                            <td style="font-weight: 600;">#{{ $td['trip_no'] }}</td>
                            <td>{{ $td['from_city'] }} &rarr; {{ $td['to_city'] }}</td>
                            <td>{{ $td['lr_numbers'] ?? '—' }}</td>
                            <td>{{ $td['lorry_receipt_numbers'] ?? '—' }}</td>
                            <td>{{ $customerName }}</td>
                            <td>{{ $supplierName }}</td>
                            <td class="report-amount">{!! format_money_pdf($td['revenue'], $currency) !!}</td>
                            <td class="report-amount">{!! format_money_pdf($td['cost'], $currency) !!}</td>
                            <td class="report-amount" style="color: {{ $td['profit'] >= 0 ? '#16a34a' : '#dc2626' }}; font-weight: 600;">
                                {!! format_money_pdf($td['profit'], $currency) !!}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="report-muted">No trip details available.</td>
                        </tr>
                    @endforelse
                    <tr class="report-total-row">
                        <td class="report-total" colspan="6">Total</td>
                        <td class="report-amount report-total">{!! format_money_pdf($totals['revenue'], $currency) !!}</td>
                        <td class="report-amount report-total">{!! format_money_pdf($totals['cost'], $currency) !!}</td>
                        <td class="report-amount report-total" style="color: {{ ($totals['profit'] ?? 0) >= 0 ? '#16a34a' : '#dc2626' }};">
                            {!! format_money_pdf($totals['profit'], $currency) !!}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
@endsection