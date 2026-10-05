<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Noto Sans', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 0; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }
        .header h1 { font-size: 20px; margin: 0; color: #111; }
        .header .meta { text-align: right; font-size: 11px; color: #6b7280; }
        .company-name { font-size: 14px; font-weight: 600; color: #111; }
        .date-range { font-size: 11px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; color: #6b7280; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        td { padding: 6px 8px; border-bottom: 1px solid #f3f4f6; font-size: 11px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { margin-top: 20px; }
        .totals table { width: 300px; margin-left: auto; }
        .totals td { border: none; padding: 4px 8px; }
        .totals .grand-total { font-weight: 700; font-size: 13px; border-top: 2px solid #e5e7eb; }
        .section-title { font-size: 14px; font-weight: 600; margin-top: 24px; margin-bottom: 8px; color: #111; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; color: #fff; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Trip Report</h1>
            @if (isset($company))
                <div class="company-name">{{ $company->name }}</div>
            @endif
        </div>
        <div class="meta">
            <div class="date-range">{{ $from_date }} — {{ $to_date }}</div>
            @if (!empty($filters))
                <div class="date-range" style="margin-top:4px;">
                    @foreach ($filters as $key => $val)
                        <span style="display:inline-block;margin-right:8px;">{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $val }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @php
        $fmt = function ($amount) {
            return number_format(abs($amount) / 100, 2);
        };
    @endphp

    @if ($type === 'customer')
        <div class="section-title">By Customer</div>
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th class="text-center">Trips</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Cost</th>
                    <th class="text-right">Profit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['customer_name'] }}</td>
                        <td class="text-center">{{ $row['trip_count'] }}</td>
                        <td class="text-right">{{ $fmt($row['revenue']) }}</td>
                        <td class="text-right">{{ $fmt($row['cost']) }}</td>
                        <td class="text-right">{{ $fmt($row['profit']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif ($type === 'lr')
        <div class="section-title">By LR Number</div>
        <table>
            <thead>
                <tr>
                    <th>LR No</th>
                    <th>Customer</th>
                    <th>Route</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Cost</th>
                    <th class="text-right">Profit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['lr_no'] ?? '—' }}</td>
                        <td>{{ $row['customer_name'] ?? '—' }}</td>
                        <td>{{ ($row['from_city'] ?? '—') . ' → ' . ($row['to_city'] ?? '—') }}</td>
                        <td class="text-right">{{ $fmt($row['revenue']) }}</td>
                        <td class="text-right">{{ $fmt($row['cost']) }}</td>
                        <td class="text-right">{{ $fmt($row['profit']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif ($type === 'supplier')
        <div class="section-title">By Supplier</div>
        <table>
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Bill No</th>
                    <th>Reference</th>
                    <th class="text-center">Trips</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Cost</th>
                    <th class="text-right">Profit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['supplier_name'] }}</td>
                        <td>{{ $row['bill_number'] }}</td>
                        <td>{{ $row['bill_reference'] }}</td>
                        <td class="text-center">{{ $row['trip_count'] }}</td>
                        <td class="text-right">{{ $fmt($row['revenue']) }}</td>
                        <td class="text-right">{{ $fmt($row['cost']) }}</td>
                        <td class="text-right">{{ $fmt($row['profit']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif ($type === 'invoice')
        <div class="section-title">By Invoice Receipt</div>
        <table>
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Customer</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Due</th>
                    <th>Status</th>
                    <th class="text-right">Profit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['invoice_number'] }}</td>
                        <td>{{ $row['customer_name'] }}</td>
                        <td class="text-right">{{ $fmt($row['total']) }}</td>
                        <td class="text-right">{{ $fmt($row['due_amount']) }}</td>
                        <td>{{ $row['paid_status'] }}</td>
                        <td class="text-right">{{ $fmt($row['profit']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (!empty($trip_details))
        <div class="section-title" style="margin-top: 28px;">Trip Details — The Money</div>
        <table>
            <thead>
                <tr>
                    <th>Trip</th>
                    <th>Route</th>
                    <th>LR No</th>
                    <th>Invoice Receipt No</th>
                    <th>Lorry Receipt No</th>
                    <th>Customer</th>
                    <th>Supplier</th>
                    <th class="text-right">Revenue</th>
                    <th class="text-right">Cost</th>
                    <th class="text-right">Profit</th>
                    <th>Bill Status</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grandRevenue = 0;
                    $grandCost = 0;
                    $grandProfit = 0;
                @endphp
                @foreach ($trip_details as $td)
                    @php
                        $grandRevenue += $td['revenue'];
                        $grandCost += $td['cost'];
                        $grandProfit += $td['profit'];
                        $customerName = $td['receivable'][0]['customer_name'] ?? '—';
                        $supplierName = $td['payable'][0]['supplier_name'] ?? '—';
                        $billStatus = $td['payable'][0]['bill_status'] ?? '—';
                    @endphp
                    <tr>
                        <td>#{{ $td['trip_no'] }}</td>
                        <td>{{ $td['from_city'] }} → {{ $td['to_city'] }}</td>
                        <td>{{ $td['lr_numbers'] ?? '—' }}</td>
                        <td>{{ $td['invoice_numbers'] ?? '—' }}</td>
                        <td>{{ $td['lorry_receipt_numbers'] ?? '—' }}</td>
                        <td>{{ $customerName }}</td>
                        <td>{{ $supplierName }}</td>
                        <td class="text-right">{{ $fmt($td['revenue']) }}</td>
                        <td class="text-right">{{ $fmt($td['cost']) }}</td>
                        <td class="text-right" style="color: {{ $td['profit'] >= 0 ? '#16a34a' : '#dc2626' }}; font-weight: 600;">{{ $fmt($td['profit']) }}</td>
                        <td>{{ $billStatus }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="border-top: 2px solid #e5e7eb; font-weight: 700;">
                    <td colspan="7" style="text-align: right;">Total</td>
                    <td class="text-right">{{ $fmt($grandRevenue) }}</td>
                    <td class="text-right">{{ $fmt($grandCost) }}</td>
                    <td class="text-right" style="color: {{ $grandProfit >= 0 ? '#16a34a' : '#dc2626' }};">{{ $fmt($grandProfit) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="totals">
        <table>
            <tr>
                <td>Total Trips</td>
                <td class="text-right">{{ $totals['trip_count'] }}</td>
            </tr>
            <tr>
                <td>Total Revenue</td>
                <td class="text-right">{{ $fmt($totals['revenue']) }}</td>
            </tr>
            <tr>
                <td>Total Cost</td>
                <td class="text-right">{{ $fmt($totals['cost']) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Total Profit</td>
                <td class="text-right">{{ $fmt($totals['profit']) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>