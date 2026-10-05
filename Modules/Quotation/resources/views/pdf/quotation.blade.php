<!DOCTYPE html>
<html>

<head>
    <title>Quotation - {{ $estimate->estimate_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

@include("app.pdf.partials.fonts")

    <style type="text/css">
        /* -- Base -- */
        body { margin: 0px; }
        table { border-collapse: collapse; }
        hr { margin: 0 30px 0 30px; color: rgba(0, 0, 0, 0.2); border: 0.5px solid #EAF1FB; }

        /* -- Header -- */
        .header-container { position: relative; width: 100%; height: 90px; left: 0px; top: 0px; margin-bottom: -40px; }
        .header-bottom-divider { color: rgba(0, 0, 0, 0.2); position: absolute; top: 90px; left: 0px; width: 100%; }
        .header-logo { margin-top: 20px; text-transform: capitalize; color: #817AE3; }
        .header { font-size: 20px; color: rgba(0, 0, 0, 0.7); }
        .wrapper { display: block; margin-top: 0px; padding-top: 16px; padding-bottom: 20px; }

        /* -- Company Details -- */
        .company-details-container { padding-top: 30px; }
        .company-address-container { padding-top: 15px; float: left; padding-left: 30px; width: 30%; text-transform: capitalize; margin-bottom: 2px; }
        .company-address-container h1 { font-size: 15px; line-height: 22px; letter-spacing: 0.05em; margin-bottom: 0px; margin-top: 10px; }
        .company-address { margin-top: 2px; text-align: left; font-size: 12px; line-height: 15px; color: #595959; width: 280px; word-wrap: break-word; }
        .estimate-details-container { float: right; padding: 10px 30px 0 0; }
        .attribute-label { font-size: 12px; line-height: 18px; padding-right: 40px; text-align: left; color: #55547A }
        .attribute-value { font-size: 12px; line-height: 18px; text-align: right; }

        /* -- Customer Address -- */
        .customer-address-container { width: 45%; padding: 0px 0 0 0px; }
        .shipping-address-container { float: right; padding-left: 40px; width: 160px; }
        .shipping-address-container--left { float: left; padding-left: 0px; }
        .shipping-address-label { font-size: 12px; line-height: 18px; padding: 0px; margin-top: 27px; margin-bottom: 0px; }
        .shipping-address-name { max-width: 160px; font-size: 15px; line-height: 22px; padding: 0px; margin: 0px; }
        .shipping-address { font-size: 12px; line-height: 15px; color: #595959; padding-top: 45px; padding-left: 40px; margin: 0px; width: 160px; word-wrap: break-word; }
        .billing-address-container { padding-top: 50px; float: left; padding-left: 30px; }
        .billing-address-label { font-size: 12px; line-height: 18px; padding: 0px; margin-top: 27px; margin-bottom: 0px; }
        .billing-address-name { max-width: 160px; font-size: 15px; line-height: 22px; padding: 0px; margin: 0px; }
        .billing-address { font-size: 12px; line-height: 15px; color: #595959; padding: 45px 0px 0px 30px; margin: 0px; width: 160px; word-wrap: break-word; }

        /* -- Items Table -- */
        .items-table-wrapper { padding-top: 35px; padding-bottom: 10px; }
        .items-table-inset { padding-left: 30px; padding-right: 30px; }
        .items-table { page-break-before: avoid; page-break-after: auto; }
        .item-table-heading { font-size: 13.5; text-align: center; color: rgba(0, 0, 0, 0.85); padding: 5px; padding-bottom: 10px; }
        tr.item-table-heading-row th { border-bottom: 0.620315px solid #E8E8E8; font-size: 12px; line-height: 18px; }
        .item-table-heading-row { margin-bottom: 10px; }
        tr.item-row td { font-size: 12px; line-height: 18px; }
        .item-cell { font-size: 13; color: #040405; text-align: center; padding: 5px; padding-top: 10px; border-color: #d9d9d9; }

        /* -- Notes -- */
        .notes { font-size: 12px; color: #595959; margin-top: 80px; margin-left: 30px; width: 442px; text-align: left; page-break-inside: avoid; }
        .notes-label { font-size: 15px; line-height: 22px; letter-spacing: 0.05em; color: #040405; width: 108px; white-space: nowrap; height: 19.87px; padding-bottom: 10px; }

        /* -- Helpers -- */
        .text-center { text-align: center }
        table .text-left { text-align: left; }
        table .text-right { text-align: right; }
        .border-0 { border: none; }
        .pr-20 { padding-right: 20px; }
        .pl-0 { padding-left: 0; }
        .company-address h3, .customer-address-container h3, .billing-address h3, .shipping-address h3 { margin-top: 0; margin-bottom: 6px; }
    </style>
</head>

<body>
    <div class="header-container">
        <table width="100%">
            <tr>
                <td class="text-center">
                    @if ($logo)
                        <img class="header-logo" style="height:50px" src="{{ \App\Platform\Pdf\Rendering\ImageUtils::toBase64Src($logo) }}" alt="Company Logo">
                    @else
                        @if ($estimate->customer->company)
                            <h2 class="header-logo"> {{ $estimate->customer->company->name }} </h2>
                        @endif
                    @endif
                </td>
            </tr>
        </table>
        <hr class="header-bottom-divider" />
    </div>

    <div class="wrapper">
        <div class="company-details-container">
            <div class="company-address-container company-address">
                {!! $company_address !!}
            </div>

            <div class="estimate-details-container">
                <table class="estimate-details-table">
                    <tr>
                        <td class="attribute-label">Quotation Number</td>
                        <td class="attribute-value"> &nbsp;{{ $estimate->estimate_number }}</td>
                    </tr>
                    <tr>
                        <td class="attribute-label">Quotation Date</td>
                        <td class="attribute-value"> &nbsp;{{ $estimate->formattedEstimateDate }}</td>
                    </tr>
                    <tr>
                        <td class="attribute-label">Valid Till</td>
                        <td class="attribute-value"> &nbsp;{{ $estimate->formattedExpiryDate }}</td>
                    </tr>
                    @include('app.pdf.partials.document-custom-fields', ['document' => $estimate])
                </table>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="customer-address-container">
            @if ($billing_address !== '<br />')
                <div class="billing-address-container billing-address">
                    @if ($billing_address)
                        <b>Quotation To</b> <br>
                        {!! $billing_address !!}
                    @endif
                </div>
            @endif

            <div @if ($billing_address !== '<br />') class="shipping-address-container shipping-address" @else class="shipping-address-container--left shipping-address" style="padding-left:30px;" @endif>
                @if ($shipping_address)
                    <b>Ship To </b> <br>
                    {!! $shipping_address !!}
                @endif
            </div>

            <div style="clear: both;"></div>
        </div>

        <div class="items-table-wrapper" style="position:relative">
            <div class="items-table-inset">
                <table width="100%" class="items-table" cellspacing="0" border="0">
                    <tr class="item-table-heading-row">
                        <th width="4%" class="pr-20 text-right item-table-heading">#</th>
                        <th width="20%" class="pl-0 text-left item-table-heading">Station Name</th>
                        <th class="text-right item-table-heading">9 mt</th>
                        <th class="text-right item-table-heading">10 mt</th>
                        <th class="text-right item-table-heading">12 mt</th>
                        <th class="text-right item-table-heading">15 mt</th>
                        <th class="text-right item-table-heading">18 mt</th>
                        <th class="text-right item-table-heading">24 mt</th>
                        <th class="text-right item-table-heading">30 mt</th>
                    </tr>
                    @php
                        $sl = 1;
                        $capacities = ['9', '10', '12', '15', '18', '24', '30'];
                    @endphp
                    @foreach ($estimate->items as $item)
                        <tr class="item-row">
                            <td class="pr-20 text-right item-cell" style="vertical-align: top;">{{ $sl }}</td>
                            <td class="pl-0 text-left item-cell">{{ $item->tr_station_name ?: $item->name }}</td>
                            @foreach ($capacities as $mt)
                                <td class="text-right item-cell" style="vertical-align: top;">
                                    {{ number_format((float) ($item->{"tr_rate_{$mt}mt"} ?? 0), 2) }}
                                </td>
                            @endforeach
                        </tr>
                        @php $sl++; @endphp
                    @endforeach
                </table>
            </div>
        </div>

        <div class="notes">
            @if ($notes)
                <div class="notes-label">Notes</div>
                {!! $notes !!}
            @endif
        </div>
    </div>
</body>

</html>
