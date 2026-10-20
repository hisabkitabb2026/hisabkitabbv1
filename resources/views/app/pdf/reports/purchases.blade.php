@extends('app.pdf.reports.partials.layout')

@section('report-title', __('pdf_purchases_report_label'))
@section('footer-label', __('pdf_cost_including_tax_label'))
@section('footer-value'){!! format_money_pdf($report['purchases']['gross'], $currency) !!}@endsection

@section('report-body')
    @php
        $cashRows = [
            __('pdf_direct_expenses_label') => 'direct_expenses',
            __('pdf_supplier_payments_label') => 'supplier_payments',
            __('pdf_supplier_refunds_label') => 'supplier_refunds',
        ];

        $payableRows = [
            __('pdf_outstanding_label') => 'outstanding',
            __('pdf_overdue_label') => 'overdue',
            __('pdf_due_soon_label') => 'due_soon',
            __('pdf_due_later_label') => 'due_later',
            __('pdf_available_advances_label') => 'available_advances',
            __('pdf_available_credits_label') => 'available_credits',
        ];
    @endphp

    <p class="report-muted">{{ __('pdf_supplier_label') }}: {{ $supplier?->name ?? __('pdf_all_suppliers_label') }}</p>
    <div class="report-section">
        <p class="report-section-heading">@lang('pdf_cash_movement_label')</p>
        <p class="report-muted">@lang('pdf_purchases_cash_note')</p>
        <table class="report-table">
            <tbody>
                @foreach ($cashRows as $label => $key)
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="report-amount">{!! format_money_pdf($report['cash'][$key], $currency) !!}</td>
                    </tr>
                @endforeach
                <tr class="report-total-row">
                    <td class="report-total">@lang('pdf_net_cash_out_label')</td>
                    <td class="report-amount report-total">{!! format_money_pdf($report['cash']['net_cash_out'], $currency) !!}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="report-section">
        <p class="report-section-heading">@lang('pdf_purchase_costs_label')</p>
        <table class="report-table">
            <thead>
                <tr>
                    <th>@lang('pdf_category_label')</th>
                    <th class="report-amount">@lang('pdf_cost_before_tax_label')</th>
                    <th class="report-amount">@lang('pdf_tax_amount_label')</th>
                    <th class="report-amount">@lang('pdf_cost_including_tax_label')</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['categories'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        @foreach (['net', 'tax', 'gross'] as $key)
                            <td class="report-amount">{!! format_money_pdf($row[$key], $currency) !!}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td class="report-muted" colspan="4">@lang('pdf_report_no_records')</td>
                    </tr>
                @endforelse
                <tr class="report-total-row">
                    <td class="report-total">@lang('pdf_total')</td>
                    @foreach (['net', 'tax', 'gross'] as $key)
                        <td class="report-amount report-total">{!! format_money_pdf($report['purchases'][$key], $currency) !!}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    <div class="report-section">
        <p class="report-section-heading">@lang('pdf_purchase_taxes_label')</p>
        <p class="report-muted">@lang('pdf_purchase_tax_basis_note')</p>
        <table class="report-table">
            <thead>
                <tr>
                    <th>@lang('pdf_report_tax_type_label')</th>
                    <th class="report-amount">@lang('pdf_amount_label')</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['taxes'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="report-amount">{!! format_money_pdf($row['amount'], $currency) !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="report-muted" colspan="2">@lang('pdf_report_no_records')</td>
                    </tr>
                @endforelse
                <tr class="report-total-row">
                    <td class="report-total">@lang('pdf_total')</td>
                    <td class="report-amount report-total">{!! format_money_pdf($report['purchases']['tax'], $currency) !!}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="report-section">
        <p class="report-section-heading">@lang('pdf_current_payables_label')</p>
        <p class="report-muted">{{ __('pdf_payables_as_of_note', ['date' => $as_of_date]) }}</p>
        <table class="report-table">
            <tbody>
                @foreach ($payableRows as $label => $key)
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="report-amount">{!! format_money_pdf($report['payables'][$key], $currency) !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="report-section">
        <p class="report-section-heading">@lang('pdf_open_bills_label')</p>
        <table class="report-table">
            <thead>
                <tr>
                    <th>@lang('pdf_bill_label')</th>
                    <th>@lang('pdf_supplier_label')</th>
                    <th class="report-col-date">@lang('pdf_due_date_label')</th>
                    <th class="report-amount report-col-amount">@lang('pdf_outstanding_label')</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($report['aging'] as $bill)
                    <tr>
                        <td>{{ $bill->number }}</td>
                        <td>{{ $bill->supplier?->name }}</td>
                        <td>{{ $bill->due_date ? \Carbon\CarbonImmutable::parse($bill->due_date)->translatedFormat($date_pattern) : '-' }}</td>
                        <td class="report-amount">{!! format_money_pdf($bill->due_amount, $bill->currency) !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="report-muted" colspan="4">@lang('pdf_report_no_records')</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (! empty($report['lorry_receipts']['rows']))
        <style>
            .lr-details-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 6px;
            }
            .lr-details-table th {
                font-size: 7.5px;
                line-height: 10px;
                padding: 4px 2px;
                border-bottom: 0.8px solid #55547A;
                text-transform: uppercase;
                color: #55547A;
                text-align: left;
                vertical-align: bottom;
            }
            .lr-details-table td {
                font-size: 8px;
                line-height: 11px;
                padding: 4px 2px;
                border-bottom: 0.5px solid #EAEAEA;
                vertical-align: middle;
                color: #040405;
            }
            .lr-details-table .report-amount {
                text-align: right;
                white-space: nowrap;
            }
            .lr-details-table .report-col-date {
                text-align: center;
                white-space: nowrap;
            }
            .lr-details-table tr.report-total-row td {
                font-size: 8.5px;
                font-weight: bold;
                padding: 6px 2px;
                border-top: 1.2px solid #55547A;
                border-bottom: 1.2px solid #55547A;
                color: #040405;
            }
        </style>
        <div class="report-section">
            <p class="report-section-heading">Lorry Receipt Transport Details</p>
            <table class="report-table lr-details-table">
                <thead>
                    <tr>
                        <th style="width: 7%;">Challan No</th>
                        <th style="width: 8%;">LR Number</th>
                        <th style="width: 10%;">Lorry No</th>
                        <th style="width: 17%;">Paid To</th>
                        <th class="report-amount" style="width: 10%;">Lorry Hire</th>
                        <th class="report-amount" style="width: 9%;">Other Charges</th>
                        <th class="report-col-date" style="width: 9%;">Advance On</th>
                        <th class="report-amount" style="width: 10%;">Advance Paid</th>
                        <th class="report-amount" style="width: 10%;">Remaining Paid</th>
                        <th class="report-col-date" style="width: 10%;">Final Balance Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['lorry_receipts']['rows'] as $lr)
                        <tr>
                            <td>{{ $lr['challan_no'] }}</td>
                            <td>{{ $lr['lr_no'] ?: '—' }}</td>
                            <td>{{ $lr['lorry_no'] ?: '—' }}</td>
                            <td>{{ $lr['paid_to'] ?: '—' }}</td>
                            <td class="report-amount">{!! format_money_pdf($lr['lorry_hire'], $currency) !!}</td>
                            <td class="report-amount">{!! format_money_pdf($lr['other_charges'], $currency) !!}</td>
                            <td class="report-col-date">{{ $lr['advance_on'] ? \Carbon\CarbonImmutable::parse($lr['advance_on'])->translatedFormat($date_pattern) : '—' }}</td>
                            <td class="report-amount">{!! format_money_pdf($lr['advance_paid'], $currency) !!}</td>
                            <td class="report-amount">{!! format_money_pdf($lr['remaining_paid'], $currency) !!}</td>
                            <td class="report-col-date">{{ $lr['final_balance_date'] ? \Carbon\CarbonImmutable::parse($lr['final_balance_date'])->translatedFormat($date_pattern) : '—' }}</td>
                        </tr>
                    @endforeach
                    <tr class="report-total-row">
                        <td class="report-total" colspan="4">Total ({{ $report['lorry_receipts']['totals']['count'] }} Challans)</td>
                        <td class="report-amount report-total">{!! format_money_pdf($report['lorry_receipts']['totals']['total_hire'], $currency) !!}</td>
                        <td class="report-amount report-total">{!! format_money_pdf($report['lorry_receipts']['totals']['total_other_charges'], $currency) !!}</td>
                        <td></td>
                        <td class="report-amount report-total">{!! format_money_pdf($report['lorry_receipts']['totals']['total_advance'], $currency) !!}</td>
                        <td class="report-amount report-total">{!! format_money_pdf($report['lorry_receipts']['totals']['total_remaining_paid'], $currency) !!}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
@endsection
