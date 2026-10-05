<!DOCTYPE html>
<html>

<head>
    <title>LR Receipt - {{ $invoice->invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    @include("app.pdf.partials.fonts")

    <style type="text/css">
        /* ── Page setup: landscape A4 with comfortable print margins ── */
        @page {
            margin: 10mm;
            size: 297mm 210mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #111;
            font-size: 13px;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        /* ── Cell borders: darker for better print contrast, wrapper keeps 2px ── */
        td,
        th {
            border: 1px solid #444;
            padding: 3px 6px;
            vertical-align: top;
        }

        .wrapper {
            page-break-inside: avoid;
            width: 100%;
        }

        .wrapper > table {
            border: 2px solid #000;
        }

        .no-border {
            border: 0;
        }

        /* ── Print rules: repeat headers, avoid row splits across pages ── */
        @media print {
            thead {
                display: table-header-group;
            }
            tr {
                page-break-inside: avoid;
            }
        }

        .jurisdiction {
            font-size: 10px;
            line-height: 12px;
            margin-bottom: 4px;
            text-align: right;
            text-decoration: underline;
        }

        .jurisdiction-top {
            font-size: 10px;
            line-height: 12px;
            margin-bottom: 4px;
            text-align: right;
            text-decoration: underline;
        }

        .header-left {
            border-right: 2px solid #444 !important;
            padding: 0;
            vertical-align: top;
            width: 61%;
        }

        .header-right {
            padding: 0;
            vertical-align: top;
            width: 39%;
        }

        /* ── Brand row: horizontal layout matching office_invoice ── */
        .brand-row {
            background-color: #ffffff;
            border-bottom: 2px solid #000;
            table-layout: fixed;
            width: 100%;
        }

        .brand-row td {
            border: 0;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .logo-cell {
            padding-left: 14px !important;
            padding-right: 8px !important;
            text-align: center;
            width: 22%;
        }

        .company-logo {
            display: block;
            margin: 0 auto;
            max-height: 72px;
            max-width: 110px;
        }

        .brand-mark {
            color: #27324a;
            font-size: 42px;
            font-weight: bold;
            letter-spacing: -4px;
            line-height: 44px;
            text-align: center;
        }

        .brand-small {
            display: block;
            font-size: 9px;
            letter-spacing: 0;
            line-height: 11px;
            margin-top: 2px;
        }

        .company-cell {
            text-align: left;
            width: 78%;
        }

        .company-name {
            color: #111;
            font-size: 23px;
            font-weight: bold;
            line-height: 26px;
            margin-top: 1px;
        }

        .company-tagline {
            font-size: 13px;
            font-weight: bold;
            line-height: 15px;
        }

        .company-address {
            font-size: 13px;
            line-height: 16px;
            margin-top: 2px;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .company-contact {
            font-size: 13px;
            font-weight: bold;
            line-height: 16px;
            margin-top: 2px;
        }

        .header-left table,
        .header-right table {
            border: 0;
        }

        .top-detail-table {
            table-layout: fixed;
        }

        .top-detail-table td {
            font-size: 13px;
            min-height: 24px;
            padding: 4px 7px;
            vertical-align: middle;
        }

        .top-detail-table .tax-line {
            font-size: 13px;
            min-height: 28px;
        }

        .party-table {
            border-top: 2px solid #000 !important;
        }

        .party-table td {
            border-bottom: 0;
            border-top: 0;
        }

        /* ── Party cells: min-height + overflow protection + light bg ── */
        .party-cell {
            background-color: #fafafa;
            border-left: 1px solid #ccc;
            min-height: 132px;
            padding: 6px 8px;
            width: 50%;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        /* First party cell: no left border (it's the leftmost) */
        .party-cell:first-child {
            border-left: 0;
        }

        .party-lines {
            border-bottom: 1px solid #ddd;
            font-size: 12.5px;
            min-height: 24px;
            line-height: 22px;
            margin-top: 0;
        }

        .party-details {
            font-size: 12.5px;
            min-height: 59px;
            line-height: 16px;
            padding-top: 4px;
        }

        .side-cell {
            padding: 0;
            width: 39%;
        }

        .side-table td {
            min-height: 20px;
            padding: 2px 6px;
            vertical-align: middle;
        }

        .docket-no {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-align: left;
        }

        .owner-risk {
            font-size: 14px;
            font-weight: bold;
        }

        .tax-line {
            font-size: 13.5px;
            font-weight: bold;
            line-height: 16px;
            overflow-wrap: anywhere;
        }

        .goods {
            border-top: 2px solid #000;
            table-layout: fixed;
        }

        .goods td {
            font-size: 13px;
            min-height: 22px;
            line-height: 15px;
            padding: 4px 6px;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .goods .large {
            min-height: 36px;
        }

        .delivery-cell {
            font-size: 13px;
            line-height: 15px;
        }

        .eway-inline {
            border-top: 1px solid #444;
            margin: 15px -6px 0;
            padding: 4px 6px 0;
        }

        .left-panel {
            padding: 0;
            vertical-align: top;
            width: 100%;
        }

        .freight-panel {
            padding: 0;
            vertical-align: top;
            width: 100%;
        }

        /* ── Charges table: fixed layout, header bg, zebra, net-row highlight ── */
        .charges {
            table-layout: fixed;
        }

        .charges th {
            background-color: #e0e0e0;
            border: 1px solid #000;
            font-size: 12px;
            font-weight: bold;
            min-height: 28px;
            line-height: 14px;
            text-align: center;
            vertical-align: middle;
        }

        .charges td {
            font-size: 13px;
            min-height: 22px;
            line-height: 15px;
            padding: 4px 6px;
        }

        /* Zebra striping via class (dompdf :nth-child unreliable) */
        .charges .alt-row {
            background-color: #eef2f7;
        }

        /* Net Amount row: highlighted like grand total in office_invoice */
        .charges .net-row td {
            background-color: #d0d0d0;
            border-top: 2px solid #000;
            font-size: 15px;
            font-weight: bold;
            min-height: 28px;
            padding: 6px;
        }

        .mode {
            background-color: #ffffff;
            font-size: 15px;
            font-weight: bold;
            line-height: 16px;
            text-align: center;
            vertical-align: middle;
        }

        .mode-struck {
            color: #666;
            text-decoration: line-through;
        }

        /* display: inline (not inline-block) — dompdf renders this reliably */
        .mode-selected {
            border-bottom: 1px solid #111;
            display: inline;
            padding-bottom: 1px;
        }

        .copy-label-box {
            border: 1px solid #444;
            background-color: #f8f8f8;
            font-size: 12px;
            font-weight: normal;
            min-height: 60px;
            line-height: 16px;
            padding: 6px 8px;
            text-align: left;
        }

        .goods-fill {
            min-height: 18px;
        }

        .footer-left {
            table-layout: fixed;
        }

        .footer-left td {
            min-height: 88px;
        }

        /* ── Declaration: readable font, min-height, no overflow:hidden ── */
        .declaration {
            font-size: 10.5px;
            line-height: 13.5px;
            min-height: 51px;
            padding: 4px 6px;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .declaration-title {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .declaration-item {
            margin-bottom: 2px;
            padding-left: 10px;
            text-indent: -10px;
        }

        .agreement {
            border-top: 1px solid #444;
            font-size: 12.5px;
            font-weight: bold;
            min-height: 36px;
            line-height: 15px;
            padding-top: 7px;
            text-align: center;
        }

        .consignee-sign {
            min-height: 88px;
            line-height: 15px;
            padding: 4px 6px;
        }

        .gst-payable {
            border: 1px solid #444;
            background-color: #f5f5f5;
            font-size: 14px;
            font-weight: bold;
            min-height: 42px;
            line-height: 17px;
            padding: 8px 6px;
            text-align: center;
            vertical-align: middle;
        }

        .for-company {
            border-bottom: 0 !important;
            border-top: 2px solid #000 !important;
            font-size: 16px;
            font-weight: bold;
            min-height: 49px;
            line-height: 20px;
            padding-top: 12px;
            position: relative;
            text-align: center;
        }

        .signature-image {
            display: block;
            height: 34px;
            left: 0;
            margin: 0 auto;
            max-width: 180px;
            object-fit: contain;
            position: absolute;
            right: 0;
            top: 27px;
        }

        .company-separator {
            display: none;
        }

        /* ── Label styling: distinct from values for visual hierarchy ── */
        .label {
            color: #555;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        /* Party/section labels: slightly larger for key section headers */
        .party-cell .label {
            color: #333;
            font-size: 12px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* ── Value styling: distinct from labels — values in BOLD ── */
        .value {
            color: #111;
            font-size: 13px;
            font-weight: bold;
        }

        /* ── Page-break protection for key sections ── */
        .charges,
        .party-table,
        .goods {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
@php
    $normalize = function ($value) {
        return strtoupper(trim(preg_replace('/[^A-Z0-9]+/i', '_', (string) $value), '_'));
    };

    $fieldValue = function ($fields, $keys) use ($normalize) {
        $keys = collect((array) $keys)->map($normalize);

        foreach ($fields as $field) {
            if (! $field->customField) {
                continue;
            }

            $candidates = collect([
                $field->customField->slug,
                $field->customField->name,
                $field->customField->label,
            ])->filter()->map($normalize);

            foreach ($keys as $key) {
                if (
                    $candidates->contains($key)
                    || $candidates->contains('CUSTOM_INVOICE_'.$key)
                    || $candidates->contains('CUSTOM_Invoice_'.$key)
                    || $candidates->contains('CUSTOM_ITEM_'.$key)
                    || $candidates->contains('CUSTOM_Item_'.$key)
                    || $candidates->contains('CUSTOM_CUSTOMER_'.$key)
                    || $candidates->contains('CUSTOM_Customer_'.$key)
                ) {
                    return $field->defaultAnswer;
                }
            }
        }

        return '';
    };

    $numericField = function ($value) {
        return (float) preg_replace('/[^0-9.\-]/', '', (string) $value);
    };

    $invoiceField = function ($keys) use ($invoice, $fieldValue) {
        $aliases = [
            'from' => ['tr_from_name', 'tr_from_code', 'from_name', 'from_code'],
            'from_location' => ['tr_from_name', 'tr_from_code', 'from_name', 'from_code'],
            'to' => ['tr_to_name', 'tr_to_code', 'to_name', 'to_code'],
            'to_location' => ['tr_to_name', 'tr_to_code', 'to_name', 'to_code'],
            'truck_no' => ['tr_truck_no', 'tr_lorry_no', 'truck_no', 'lorry_no'],
            'lorry_no' => ['tr_lorry_no', 'tr_truck_no', 'lorry_no', 'truck_no'],
            'e_way_bill_no' => ['tr_eway_bill_no', 'eway_bill_no', 'e_way_bill_no'],
            'eway_bill_no' => ['tr_eway_bill_no', 'eway_bill_no', 'e_way_bill_no'],
            'actual_weight' => ['tr_actual_weight', 'actual_weight'],
            'charged_weight' => ['tr_charged_weight', 'charged_weight', 'charge_weight'],
            'charge_weight' => ['tr_charged_weight', 'charged_weight', 'charge_weight'],
            'no_of_articles' => ['tr_no_of_articles', 'no_of_articles', 'tr_no_of_packages', 'no_of_packages'],
            'articles' => ['tr_no_of_articles', 'no_of_articles'],
            'packing' => ['tr_packing', 'packing'],
            'description_of_goods' => ['tr_description_goods', 'description_goods', 'description_of_goods'],
            'description_goods' => ['tr_description_goods', 'description_goods', 'description_of_goods'],
            'hsn_code' => ['tr_hsn_code', 'hsn_code', 'hsn'],
            'delivery_at' => ['tr_delivery_at', 'delivery_at'],
            'goods_value' => ['tr_goods_value', 'goods_value'],
            'pod_required' => ['tr_pod_required', 'pod_required'],
            'time' => ['tr_time', 'time'],
            'basic_freight' => ['tr_basic_freight', 'basic_freight'],
            'local_collection' => ['tr_local_collection', 'local_collection'],
            'door_delivery' => ['tr_door_delivery', 'door_delivery'],
            'hamali' => ['tr_hamali', 'hamali'],
            'docket_charge' => ['tr_docket_charge', 'docket_charge'],
            'other_charge' => ['tr_other_charge', 'other_charge'],
            'fov' => ['tr_fov', 'fov'],
            'net_amount' => ['tr_net_amount', 'net_amount'],
            'mode_of_payment' => ['tr_mode_of_payment', 'mode_of_payment'],
            'gst_tax_payable_by' => ['tr_gst_payable_by', 'gst_payable_by', 'gst_tax_payable_by'],
            'gst_payable_by' => ['tr_gst_payable_by', 'gst_payable_by', 'gst_tax_payable_by'],
            'consignor' => ['tr_consignor', 'consignor'],
            'consignee' => ['tr_consignee', 'consignee'],
            'consignor_phone' => ['tr_consignor_phone', 'consignor_phone', 'consignor_phone_no'],
            'consignor_phone_no' => ['tr_consignor_phone', 'consignor_phone', 'consignor_phone_no'],
            'consignee_phone' => ['tr_consignee_phone', 'consignee_phone', 'consignee_phone_no'],
            'consignee_phone_no' => ['tr_consignee_phone', 'consignee_phone', 'consignee_phone_no'],
            'consignor_gst' => ['tr_consignor_gst', 'consignor_gst', 'consignor_gst_no'],
            'consignor_gst_no' => ['tr_consignor_gst', 'consignor_gst', 'consignor_gst_no'],
            'consignee_gst' => ['tr_consignee_gst', 'consignee_gst', 'consignee_gst_no'],
            'consignee_gst_no' => ['tr_consignee_gst', 'consignee_gst', 'consignee_gst_no'],
            'gstin' => ['gstin', 'gst_no'],
            'gst_no' => ['gst_no', 'gstin'],
            'pan' => ['pan_no', 'pan'],
            'pan_no' => ['pan_no', 'pan'],
        ];

        foreach ((array) $keys as $rawKey) {
            $normalizedKey = strtolower(trim($rawKey));

            $candidates = $aliases[$normalizedKey] ?? [];
            array_unshift($candidates, $normalizedKey);
            if (! str_starts_with($normalizedKey, 'tr_')) {
                $candidates[] = 'tr_' . $normalizedKey;
            }

            foreach ($candidates as $cand) {
                if (isset($invoice->$cand) && trim((string) $invoice->$cand) !== '') {
                    return $invoice->$cand;
                }
                $camel = \Illuminate\Support\Str::camel($cand);
                if (isset($invoice->$camel) && trim((string) $invoice->$camel) !== '') {
                    return $invoice->$camel;
                }
            }
        }

        // Fallback to custom fields relationship
        return $fieldValue($invoice->fields ?? [], $keys);
    };

    $formatAddress = function ($customer) {
        if (! $customer) {
            return '';
        }
        $address = $customer->billingAddress ?: ($customer->shippingAddress ?: $customer->addresses?->first());
        if (! $address) {
            return '';
        }
        $street = array_filter([$address->address_street_1, $address->address_street_2]);
        $cityState = implode(', ', array_filter([$address->city, $address->state]));
        $cityStateZip = trim($cityState . ($address->zip ? ' ' . $address->zip : ''));

        $lines = array_merge($street, array_filter([$cityStateZip]));
        return implode("\n", array_filter(array_map('trim', $lines)));
    };

    $parseParty = function ($partyText) {
        $lines = collect(explode("\n", (string) $partyText))
            ->map(fn($line) => trim($line))
            ->filter(fn($line) => $line !== '')
            ->values();

        $name = $lines->first() ?: '';
        $addressLines = $lines->slice(1)->values()->all();

        return [
            'name' => $name,
            'address' => implode("\n", $addressLines),
        ];
    };

    $item = $invoice->items?->first();
    $itemField = function ($keys) use ($item, $fieldValue) {
        return $item ? $fieldValue($item->fields ?? [], $keys) : '';
    };

    $moneyText = function ($paise) use ($invoice) {
        if (! $paise) {
            return '';
        }

        $currency = $invoice->customer ? $invoice->customer->currency : null;

        return format_money_pdf((int) round($paise), $currency);
    };

    $companyName = $invoiceField(['company_name']) ?: ($invoice->company?->name ?: '');
    $companyInitials = collect(preg_split('/\s+/', trim($companyName)))
        ->filter()
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->take(2)
        ->implode('');
    $companyTagline = $invoice->company?->tagline ?: '';
    $companyTopHeading = $invoice->company?->top_heading ?: 'Subject to Jurisdiction';
    $companyPhone = $invoice->company?->address?->phone;
    $companyEmail = $invoice->company?->address?->email ?: ($invoice->company?->notification_email ?: \App\Domains\Accounts\Models\CompanySetting::getSetting('notification_email', $invoice->company_id));
    $mobile = $companyPhone ?: ($invoiceField(['mobile', 'phone']) ?: '');
    $email = $invoiceField(['email']) ?: ($companyEmail ?: '');
    $displayCompanyAddress = trim(strip_tags((string) ($company_address ?? '')))
        ? preg_replace('/^\s*<h[1-6][^>]*>.*?<\/h[1-6]>\s*/is', '', (string) $company_address)
        : '';
    if ($companyName) {
        $cleanNamePattern = '/^\s*(?:<[^>]+>)*\s*' . preg_quote($companyName, '/') . '\s*(?:<\/[^>]+>)*\s*(?:<br\s*\/?>)?/i';
        $displayCompanyAddress = preg_replace($cleanNamePattern, '', $displayCompanyAddress);
    }
    $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*E-?mail\s*:?\s*[^<\r\n]+/i', '', $displayCompanyAddress);
    $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*Mob(?:ile)?\.?\s*:?\s*[^<\r\n]+/i', '', $displayCompanyAddress);
    if ($mobile) {
        $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*'.preg_quote($mobile, '/').'\s*/i', '', $displayCompanyAddress);
    }
    if ($email) {
        $displayCompanyAddress = preg_replace('/(?:<br\s*\/?>|\s)*'.preg_quote($email, '/').'\s*/i', '', $displayCompanyAddress);
    }
    if ($displayCompanyAddress === '' && $invoice->company) {
        $address = $invoice->company->address;
        $displayCompanyAddress = implode('<br>', array_filter([
            e($address?->address_street_1),
            e($address?->address_street_2),
            e(trim(implode(' ', array_filter([$address?->city, $address?->state, $address?->zip])))),
            e($address?->country_name),
        ]));
    }
    // PAN No: Tax Identification Number (tax_id) from /admin/settings/company-info, or custom field, or company pan_no
    $panNo = $invoiceField(['pan_no', 'pan']) ?: ($invoice->company?->tax_id ?: ($invoice->company?->pan_no ?: ''));
    // GSTIN: VAT Identification Number (vat_id) from /admin/settings/company-info, or custom field, or company gstin
    $companyGstin = $invoiceField(['gstin', 'gst_no']) ?: ($invoice->company?->vat_id ?: ($invoice->company?->gstin ?: ''));
    $companyEnrollmentNo = $invoice->company?->enrollment_no ?: $invoiceField(['enrollment_no', 'enrollment']);
    $companyTaxIdentityLabel = $companyEnrollmentNo ? 'Enrollment No' : 'GSTIN';
    $companyTaxIdentityValue = $companyEnrollmentNo ?: $companyGstin;

    $basicFreight = $invoiceField(['basic_freight']);
    $localCollection = $invoiceField(['local_collection']);
    $doorDelivery = $invoiceField(['door_delivery']);
    $hamali = $invoiceField(['hamali']);
    $docketCharge = $invoiceField(['docket_charge']) ?: 100;
    $otherCharge = $invoiceField(['other_charge']);
    $fov = $invoiceField(['fov']);
    $calculatedNet = (
        $numericField($basicFreight)
        + $numericField($localCollection)
        + $numericField($doorDelivery)
        + $numericField($hamali)
        + $numericField($docketCharge)
        + $numericField($otherCharge)
        + $numericField($fov)
    ) * 100;
    $storedNet = $numericField($invoiceField(['net_amount'])) * 100;
    $netAmount = $calculatedNet > 0 ? $calculatedNet : ($storedNet > 0 ? $storedNet : 0);

    $modeOfPayment = $invoiceField(['mode_of_payment']) ?: 'TO PAY';
    $selectedMode = $normalize($modeOfPayment);
    if ($selectedMode === 'TO_BE_BILLED') {
        $selectedMode = 'TO_BE_BILLED_AT';
    }
    $modeLabel = function (string $label) use ($normalize, $selectedMode) {
        if ($normalize($label) === $selectedMode) {
            return '<span class="mode-selected">'.e($label).'</span>';
        }

        return '<span class="mode-struck">'.e($label).'</span>';
    };

    $gstPayableBy = $invoiceField(['gst_tax_payable_by', 'gst_payable_by']) ?: 'Consignor / Consignee';

    // Consignor (Customer)
    $consignorCustomer = $invoice->customer;
    $parsedConsignor = $parseParty($invoiceField(['consignor', 'tr_consignor']));
    $consignorName = $consignorCustomer?->name ?: ($parsedConsignor['name'] ?: '');
    $consignorAddrFromCust = $formatAddress($consignorCustomer);
    $consignorAddress = (strlen($consignorAddrFromCust) > strlen($parsedConsignor['address']))
        ? $consignorAddrFromCust
        : ($parsedConsignor['address'] ?: $consignorAddrFromCust);
    $consignorPhone = $invoiceField(['consignor_phone', 'consignor_phone_no', 'tr_consignor_phone'])
        ?: ($consignorCustomer?->phone ?: ($consignorCustomer?->billingAddress?->phone ?: ''));
    $consignorGstin = $invoiceField(['consignor_gst', 'consignor_gst_no', 'tr_consignor_gst'])
        ?: ($consignorCustomer?->tax_id ?: '');

    // Consignee
    $consigneeCustomer = $invoice->relationLoaded('consigneeCustomer')
        ? $invoice->consigneeCustomer
        : ($invoice->tr_consignee_customer_id ? \App\Domains\Contacts\Models\Customer::with(['addresses', 'billingAddress', 'shippingAddress'])->find($invoice->tr_consignee_customer_id) : null);
    $parsedConsignee = $parseParty($invoiceField(['consignee', 'tr_consignee']));
    $consigneeName = $consigneeCustomer?->name ?: ($parsedConsignee['name'] ?: '');
    $consigneeAddrFromCust = $formatAddress($consigneeCustomer);
    $consigneeAddress = (strlen($consigneeAddrFromCust) > strlen($parsedConsignee['address']))
        ? $consigneeAddrFromCust
        : ($parsedConsignee['address'] ?: $consigneeAddrFromCust);
    $consigneePhone = $invoiceField(['consignee_phone', 'consignee_phone_no', 'tr_consignee_phone'])
        ?: ($consigneeCustomer?->phone ?: ($consigneeCustomer?->billingAddress?->phone ?: ''));
    $consigneeGstin = $invoiceField(['consignee_gst', 'consignee_gst_no', 'tr_consignee_gst'])
        ?: ($consigneeCustomer?->tax_id ?: '');

    $docketNumber = $invoice->invoice_number;
    $descriptionOfGoods = trim((string) $invoiceField(['description_of_goods', 'description_goods']));
    $noOfArticles = trim((string) $invoiceField(['no_of_articles', 'articles', 'no_of_packages', 'packages']));

    if (preg_match('/^LR Receipt\s+\d+$/i', $descriptionOfGoods)) {
        $descriptionOfGoods = '';
    }

    if ($noOfArticles === '1' && $descriptionOfGoods === '') {
        $noOfArticles = '';
    }
    $signaturePath = base_path('resources/static/img/PDF/authorized_signature.jpeg');

    // Auto-fit font sizing: shrinks font size for text that would overflow
    // its container. Each block shrinks independently so other blocks are
    // not disturbed.
    $getFontForWidth = function ($value, $widthLimit, $baseSize = 11.5, $minSize = 6.5) {
        $length = strlen((string) $value);
        if ($length === 0) {
            return '';
        }
        $estimatedWidth = $length * ($baseSize * 0.55);
        if ($estimatedWidth > $widthLimit) {
            $shrunkSize = ($widthLimit / $length) / 0.55;
            return 'font-size: ' . number_format(max($minSize, min($baseSize, $shrunkSize)), 1) . 'px;';
        }
        return '';
    };

    // Pre-calculate auto-fit styles for key fields that commonly overflow.
    $consignorNameStyle = $getFontForWidth($consignorName, 420, 11.5, 6.5);
    $consigneeNameStyle = $getFontForWidth($consigneeName, 420, 11.5, 6.5);
    $companyNameStyle = $getFontForWidth($companyName, 500, 26, 10);
    $consignorGstinStyle = $getFontForWidth($consignorGstin, 380, 11, 6.5);
    $consigneeGstinStyle = $getFontForWidth($consigneeGstin, 380, 11, 6.5);
    $descriptionOfGoodsStyle = $getFontForWidth($descriptionOfGoods, 700, 11.8, 6.5);

    $consignorAddrStyle = '';
    $consigneeAddrStyle = '';

    // Top-detail-table fields
    $fromLocation = $invoiceField(['from']);
    $toLocation = $invoiceField(['to']);
    $truckNo = $invoiceField(['truck_no']);
    $fromStyle = $getFontForWidth($fromLocation, 250, 12, 7);
    $toStyle = $getFontForWidth($toLocation, 250, 12, 7);
    $truckNoStyle = $getFontForWidth($truckNo, 400, 12, 7);

    // Delivery At
    $deliveryAt = $invoiceField(['delivery_at']);
    $deliveryAtStyle = $getFontForWidth($deliveryAt, 480, 12, 7);

    // PAN No and tax identity
    $panNoStyle = $getFontForWidth($panNo, 200, 13.5, 7);
    $taxIdentityStyle = $getFontForWidth($companyTaxIdentityValue, 200, 13.5, 7);

    // Goods table secondary fields
    $hsnCode = $invoiceField(['hsn_code']);
    $hsnCodeStyle = $getFontForWidth($hsnCode, 480, 12, 7);
    $invoiceNo = $invoiceField(['invoice_no']);
    $invoiceNoStyle = $getFontForWidth($invoiceNo, 200, 12, 7);
    $goodsValue = $invoiceField(['goods_value']);
    $goodsValueStyle = $getFontForWidth($goodsValue, 200, 12, 7);
    $ewayBillNo = $invoiceField(['e_way_bill_no']);
    $ewayBillStyle = $getFontForWidth($ewayBillNo, 480, 12, 7);
    $actualWeight = $invoiceField(['actual_weight']);
    $chargedWeight = $invoiceField(['charged_weight']);
    $packing = $invoiceField(['packing']);
    $podRequired = $invoiceField(['pod_required']);

    // Charges table
    $basicFreightStyle = $getFontForWidth($basicFreight, 140, 12, 7);
    $localCollectionStyle = $getFontForWidth($localCollection, 140, 12, 7);
    $doorDeliveryStyle = $getFontForWidth($doorDelivery, 140, 12, 7);
    $hamaliStyle = $getFontForWidth($hamali, 140, 12, 7);
    $otherChargeStyle = $getFontForWidth($otherCharge, 140, 12, 7);
    $fovStyle = $getFontForWidth($fov, 140, 12, 7);

    $gstPayableByStyle = $getFontForWidth($gstPayableBy, 280, 13, 8);
    $companyAddrStyle = '';
    $docketNoStyle = $getFontForWidth($docketNumber, 220, 12, 7);

    $consignorData = [
        'name' => $consignorName,
        'address' => $consignorAddress,
    ];
    $consigneeData = [
        'name' => $consigneeName,
        'address' => $consigneeAddress,
    ];

    $isMulti = request()->has('multi') || request()->query('copy') === 'multi';

    if ($isMulti) {
        $renderCopies = [
            [
                'key' => 'consignee',
                'label' => 'CONSIGNEE COPY',
                'bg' => '#ffffff',
            ],
            [
                'key' => 'driver',
                'label' => 'DRIVER COPY',
                'bg' => '#eafaf1',
            ],
            [
                'key' => 'consignor',
                'label' => 'CONSIGNOR COPY',
                'bg' => '#fdf2f8',
            ],
            [
                'key' => 'file',
                'label' => 'FILE COPY',
                'bg' => '#fefde7',
            ],
        ];
    } else {
        $currentCopy = request()->query('copy');
        $bg = '#ffffff';
        if ($currentCopy === 'driver') {
            $bg = '#eafaf1';
        } elseif ($currentCopy === 'consignor') {
            $bg = '#fdf2f8';
        } elseif ($currentCopy === 'ho') {
            $bg = '#fefde7';
        }

        $copyLabelMap = [
            'consignee' => 'CONSIGNEE COPY',
            'driver' => 'DRIVER COPY',
            'consignor' => 'CONSIGNOR COPY',
            'ho' => 'H. O COPY',
            'file' => 'FILE COPY',
        ];
        $copyLabel = $currentCopy ? ($copyLabelMap[$currentCopy] ?? '') : '';

        $renderCopies = [
            [
                'key' => $currentCopy ?: 'default',
                'label' => $copyLabel ?: '',
                'bg' => $bg,
            ]
        ];
    }
@endphp

@foreach ($renderCopies as $index => $copy)
    <div style="background-color: {{ $copy['bg'] }}; @if(!$loop->last) page-break-after: always; @endif">
    <div class="jurisdiction-top">{{ $companyTopHeading }}</div>
    <div class="wrapper" style="background-color: {{ $copy['bg'] }}; @if(!$loop->last) margin-bottom: 20px; @endif">
        <table>
            <tr>
                <td class="header-left">
                    <table class="brand-row">
                        <tr>
                            <td class="logo-cell">
                                @if ($logo && file_exists($logo))
                                    <img class="company-logo" src="{{ \App\Platform\Pdf\Rendering\ImageUtils::toBase64Src($logo) }}" alt="Company Logo">
                                @else
                                    <div class="brand-mark">
                                        {{ $companyInitials }}
                                    </div>
                                @endif
                            </td>
                            <td class="company-cell">
                                <div class="company-name" style="{{ $companyNameStyle }}">{{ $companyName }}</div>
                                <div class="company-tagline">{{ $companyTagline }}</div>
                                <div class="company-address" style="{{ $companyAddrStyle }}">{!! $displayCompanyAddress !!}</div>
                                <div class="company-contact">Mob. {{ $mobile }} &nbsp;|&nbsp; E-mail : {{ $email }}</div>
                            </td>
                        </tr>
                    </table>

                    <table class="party-table">
                        <tr>
                            <td class="party-cell">
                                <div style="margin-bottom: 4px;"><span class="label">Consignor</span></div>
                                <div style="font-size: 14px; font-weight: bold; line-height: 18px; {{ $consignorNameStyle }}">{{ $consignorData['name'] }}</div>
                                <div class="party-lines party-details">{!! nl2br(e($consignorData['address'])) !!}</div>
                                <div class="party-lines"><span class="label">Phone No.:</span> <span class="value">{{ $consignorPhone }}</span></div>
                                <div class="party-lines" style="{{ $consignorGstinStyle }}"><span class="label">GST No.:</span> <span class="value">{{ $consignorGstin }}</span></div>
                            </td>
                            <td class="party-cell">
                                <div style="margin-bottom: 4px;"><span class="label">Consignee</span></div>
                                <div style="font-size: 14px; font-weight: bold; line-height: 18px; {{ $consigneeNameStyle }}">{{ $consigneeData['name'] }}</div>
                                <div class="party-lines party-details">{!! nl2br(e($consigneeData['address'])) !!}</div>
                                <div class="party-lines"><span class="label">Phone No.:</span> <span class="value">{{ $consigneePhone }}</span></div>
                                <div class="party-lines" style="{{ $consigneeGstinStyle }}"><span class="label">GST No.:</span> <span class="value">{{ $consigneeGstin }}</span></div>
                            </td>
                        </tr>
                    </table>

                    <table class="goods">
                        <tr>
                            <td width="50%" class="large"><span class="label">Description of Goods</span><br><span class="value" style="{{ $descriptionOfGoodsStyle }}">{{ $descriptionOfGoods }}</span></td>
                            <td width="24%"><span class="label">No. of Articles</span><br><span class="value">{{ $noOfArticles }}</span></td>
                            <td><span class="label">Packing</span><br><span class="value">{{ $packing }}</span></td>
                        </tr>
                        <tr>
                            <td><span class="label">HSN CODE</span><br><span class="value" style="{{ $hsnCodeStyle }}">{{ $hsnCode }}</span></td>
                            <td><span class="label">Actual Weight</span></td>
                            <td><span class="value">{{ $actualWeight }}</span></td>
                        </tr>
                        <tr>
                            <td rowspan="3" class="delivery-cell">
                                <span class="label">Delivery At.:</span><br>
                                <span class="value" style="{{ $deliveryAtStyle }}">{{ $deliveryAt }}</span>
                                <div class="eway-inline">
                                    <span class="label">E-way Bill No.:</span><br>
                                    <span class="value" style="{{ $ewayBillStyle }}">{{ $ewayBillNo }}</span>
                                </div>
                            </td>
                            <td><span class="label">Charged Weight</span></td>
                            <td><span class="value">{{ $chargedWeight }}</span></td>
                        </tr>
                        <tr>
                            <td><span class="label">Goods Value</span></td>
                            <td><span class="value" style="{{ $goodsValueStyle }}">{{ $goodsValue }}</span></td>
                        </tr>
                        <tr>
                            <td class="goods-fill"><span class="label">POD Required</span></td>
                            <td><span class="value">{{ $podRequired }}</span></td>
                        </tr>
                        <tr>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                    </table>

                    <table class="footer-left">
                        <tr>
                            <td width="50%" style="padding: 0;">
                                <div class="declaration">
                                    <div class="declaration-title">DECLARATION :</div>
                                    <div class="declaration-item">• We Have Not Taken Gst Credit As Per The Provisions Of Cenvat Credit Rules 2004 On Inputs Or Capital Goods Used For Providing Taxable Service To You.</div>
                                    <div class="declaration-item">• We Have Availed The Benefits Of Notification No. 11 &amp; 13/2017 Dated 28th June 2017.</div>
                                </div>
                                <div class="agreement">It is taken in to consideration that agrees with<br>all the terms and condition overleaf</div>
                            </td>
                            <td width="50%" class="consignee-sign">
                                <span class="label">Rubber Stamp and Signature of Consignee</span><br><br><br><br>
                                <span class="label">Phone / Mobile</span><br>
                                &nbsp;
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="header-right">
                    <div class="copy-label-box">
                        @php
                            $hasActiveCopy = in_array($copy['key'], ['consignee', 'driver', 'consignor', 'ho', 'file'], true);
                            $isCopy = fn($name) => $copy['key'] === $name;
                            $styleLine = function($name) use ($hasActiveCopy, $isCopy) {
                                if (! $hasActiveCopy) {
                                    return '';
                                }
                                return $isCopy($name) ? 'font-weight: bold; text-decoration: underline;' : 'color: #777; font-size: 11px;';
                            };
                        @endphp
                        <span style="{{ $styleLine('consignee') }}">ORIGINAL WHITE&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: CONSIGNEE COPY</span><br>
                        <span style="{{ $styleLine('driver') }}">DUPLICATE GREEN&nbsp;&nbsp;&nbsp;: DRIVER COPY</span><br>
                        <span style="{{ $styleLine('consignor') }}">TRIPLICATE PINK&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: CONSIGNOR COPY</span><br>
                        <span style="{{ $styleLine('ho') }}">YELLOW&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: H. O COPY</span><br>
                        <span style="{{ $styleLine('file') }}">DUPLICATE WHITE&nbsp;&nbsp;&nbsp;: FILE COPY</span>
                    </div>
                    <table class="top-detail-table">
                        <tr>
                            <td width="36%"><span class="label">Date :</span> <span class="value">{{ $invoice->formattedInvoiceDate }}</span></td>
                            <td><span class="label">Docket No.:</span> <span class="docket-no" style="{{ $docketNoStyle }}">{{ $docketNumber }}</span></td>
                        </tr>
                        <tr>
                            <td width="36%"><span class="label">Time :</span> <span class="value">{{ $invoiceField(['time']) }}</span></td>
                            <td><span class="label">From :</span> <span class="value" style="{{ $fromStyle }}">{{ $fromLocation }}</span></td>
                        </tr>
                        <tr>
                            <td width="36%" class="owner-risk">OWNER'S RISK</td>
                            <td><span class="label">To :</span> <span class="value" style="{{ $toStyle }}">{{ $toLocation }}</span></td>
                        </tr>
                        <tr><td colspan="2"><span class="label">Truck No.:</span> <span class="value" style="{{ $truckNoStyle }}">{{ $truckNo }}</span></td></tr>
                        <tr><td colspan="2" class="tax-line"><span class="label">PAN No.:</span> <span class="value" style="{{ $panNoStyle }}">{{ $panNo }}</span><br><span class="label">{{ $companyTaxIdentityLabel }} :</span> <span class="value" style="{{ $taxIdentityStyle }}">{{ $companyTaxIdentityValue }}</span></td></tr>
                    </table>

                    <table class="charges">
                        <tr>
                            <th width="42%">Description of<br>Freight</th>
                            <th width="34%">To Pay/Paid Rs.</th>
                            <th>Mode of<br>Payment</th>
                        </tr>
                        <tr class="alt-row">
                            <td><span class="label">Basic Freight</span></td>
                            <td class="text-right" style="{{ $basicFreightStyle }}">{{ $basicFreight }}</td>
                            <td class="mode">{!! $modeLabel('TO PAY') !!}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Local Collection</span></td>
                            <td class="text-right" style="{{ $localCollectionStyle }}">{{ $localCollection }}</td>
                            <td class="mode"></td>
                        </tr>
                        <tr class="alt-row">
                            <td><span class="label">Door Delivery</span></td>
                            <td class="text-right" style="{{ $doorDeliveryStyle }}">{{ $doorDelivery }}</td>
                            <td rowspan="3" class="mode">{!! $modeLabel('PAID') !!}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Hamali</span></td>
                            <td class="text-right" style="{{ $hamaliStyle }}">{{ $hamali }}</td>
                        </tr>
                        <tr class="alt-row">
                            <td><span class="label">Docket Charge</span></td>
                            <td class="text-right">{{ $docketCharge ? $docketCharge.'/-' : '' }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Other Charge</span></td>
                            <td class="text-right" style="{{ $otherChargeStyle }}">{{ $otherCharge }}</td>
                            <td rowspan="4" class="mode">{!! $modeLabel('TO BE BILLED AT') !!}</td>
                        </tr>
                        <tr class="alt-row">
                            <td><span class="label">F.O.V.</span></td>
                            <td class="text-right" style="{{ $fovStyle }}">{{ $fov }}</td>
                        </tr>
                        <tr>
                            <td><span class="label">Sub Total</span></td>
                            <td class="text-right">{!! $moneyText($netAmount) !!}</td>
                        </tr>
                        <tr class="net-row">
                            <td><span class="label">Net Amount</span></td>
                            <td class="text-right">{!! $moneyText($netAmount) !!}</td>
                        </tr>
                    </table>

                    <table>
                        <tr>
                            <td class="gst-payable" style="{{ $gstPayableByStyle }}">
                                GST Tax Payable By<br>{{ $gstPayableBy }}
                            </td>
                        </tr>
                        <tr>
                            <td class="for-company">
                                <div class="company-separator"></div>For {{ $companyName }}
                                @if (file_exists($signaturePath))
                                    <img class="signature-image" src="{{ \App\Platform\Pdf\Rendering\ImageUtils::toBase64Src($signaturePath) }}" alt="Signature">
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    </div>
@endforeach
</body>

</html>