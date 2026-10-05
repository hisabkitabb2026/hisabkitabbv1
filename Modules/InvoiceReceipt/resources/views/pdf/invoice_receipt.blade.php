<!DOCTYPE html>
<html>

<head>
    <title>Bill - {{ $invoice->invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    @include("app.pdf.partials.fonts")

    <style type="text/css">
        @page {
            margin: 10mm;
            size: 297mm 210mm;
        }

        * {
            box-sizing: border-box;
        }

        @media print {
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            .footer { page-break-inside: avoid; }
            .words-row { page-break-inside: avoid; }
        }

        body {
            color: #111;
            font-size: 12px;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td,
        th {
            border: 1px solid #666;
            padding: 4px 6px;
            vertical-align: top;
        }

        .no-border {
            border: 0 !important;
        }

        .invoice-shell {
            width: 100%;
        }

        .invoice-shell > .master {
            border: 2px solid #000;
        }

        .master {
            border: 0;
            table-layout: fixed;
        }

        .master > tbody > tr > td,
        .master > tbody > tr > th {
            padding: 0;
        }

        .left-zone {
            width: 65%;
        }

        .right-zone {
            width: 35%;
        }

        .right-zone table td:first-child,
        .right-zone table th:first-child {
            border-left: 0;
        }

        .brand-row {
            background: #ffffff;
            border-bottom: 2px solid #000;
            min-height: 104px;
            table-layout: fixed;
        }

        .brand-row td {
            border-left: 0;
            border-right: 0;
            border-top: 0;
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

        .brand-fallback {
            color: #111;
            font-size: 34px;
            font-weight: bold;
            line-height: 34px;
            padding-top: 8px;
        }

        .brand-fallback span {
            display: block;
            font-size: 11px;
            letter-spacing: 0;
            line-height: 13px;
        }

        .company-cell {
            text-align: center;
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
            text-align: center;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .company-contact {
            font-size: 13px;
            font-weight: bold;
            line-height: 16px;
            margin-top: 2px;
            text-align: center;
        }

        .branch-box {
            min-height: 76px;
            line-height: 14px;
            padding: 4px 6px !important;
            vertical-align: middle;
            white-space: normal;
            word-break: break-word;
        }

        .branch-label {
            font-size: 14px;
            font-weight: bold;
        }

        .branch-address {
            display: block;
            line-height: 14px;
            margin-top: 2px;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .tax-box {
            min-height: 26px;
            padding: 5px 6px !important;
        }

        .tax-box div {
            font-size: 13px;
            font-weight: normal;
            line-height: 16px;
            overflow: hidden;
            overflow-wrap: anywhere;
            white-space: normal;
            word-break: break-all;
        }

        .party-box {
            border-top: 0 !important;
            min-height: 130px;
            padding: 0 !important;
        }

        .party-head {
            table-layout: fixed;
        }

        .party-head td {
            border-bottom: 0;
            border-left: 0;
            border-right: 0;
            border-top: 0;
            font-size: 12.5px;
            line-height: 16px;
            min-height: 18px;
            padding: 3px 6px;
        }

        .party-address-lines {
            font-size: 12.5px;
            line-height: 16px;
            overflow: hidden;
            padding: 3px 6px 6px;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .party-display-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .party-gstin {
            border-top: 1px solid #111;
            bottom: 0;
            font-size: 12.5px;
            line-height: 16px;
            overflow: hidden;
            padding: 3px 6px;
            text-overflow: clip;
            white-space: nowrap;
        }

        .party-gstin b {
            font-size: 12.5px;
        }

        .bill-details td {
            font-size: 13px;
            min-height: 16px;
            padding: 3px 6px;
            vertical-align: middle;
        }

        .payment-table th,
        .payment-table td {
            border: 1px solid #000;
            font-size: 11px;
            min-height: 20px;
            overflow: hidden;
            padding: 3px 4px;
            text-align: center;
            vertical-align: middle;
            word-break: break-word;
        }

        .basis-row {
            font-size: 12px;
            line-height: 15px;
            min-height: 23px;
            padding: 3px 6px !important;
        }

        .items thead tr:first-child th {
            border-top: 2px solid #000;
        }

        .items th {
            background-color: #e8e8e8;
            border: 1px solid #000;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.3px;
            line-height: 14px;
            padding: 5px 3px;
            text-align: center;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .items .group-head th {
            min-height: 20px;
        }

        .items {
            table-layout: fixed;
        }

        .items td {
            border: 0;
            border-bottom: 1px solid #999;
            font-size: 12px;
            padding: 4px 3px;
            text-align: center;
            vertical-align: top;
        }

        .items tbody tr.alt-row td {
            background-color: #f7f7f7;
        }

        .items tbody tr:last-child td {
            border-bottom: 1px solid #000;
        }

        .words-row {
            border-top: 2px solid #000;
        }

        .words-row td {
            background-color: #e0e0e0;
            font-size: 12px;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .grand-label {
            background-color: #d0d0d0;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
        }

        .grand-total-value {
            background-color: #d0d0d0;
            font-size: 16px;
            font-weight: bold;
        }

        .footer td {
            border: 1px solid #666;
            font-size: 12px;
            padding: 5px 7px;
        }

        .footer-head td {
            min-height: 20px;
        }

        .footer-body td {
            min-height: 58px;
        }

        .terms {
            font-size: 11px;
            line-height: 14.5px;
            padding: 5px 7px;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .terms-title {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .term-item {
            margin-bottom: 3px;
            padding-left: 14px;
            text-indent: -14px;
        }

        .prepared {
            font-size: 11px;
            line-height: 14px;
            overflow: hidden;
            vertical-align: top !important;
            word-break: break-word;
        }

        .emp-box {
            border: 1px solid #111;
            display: inline-block;
            line-height: 14px;
            margin-top: 4px;
            min-height: 38px;
            overflow: hidden;
            padding-top: 9px;
            text-align: center;
            width: 52px;
            word-break: break-all;
        }

        .for-company {
            font-size: 13px;
            font-weight: bold;
            line-height: 16px;
            text-align: center;
        }

        .signature-cell {
            overflow: hidden;
        }

        .signature-table {
            border-collapse: collapse;
            width: 100%;
        }

        .signature-table td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .sig-emp {
            text-align: center;
            width: 60px;
        }

        .sig-image-area {
            min-height: 42px;
            text-align: center;
        }

        .signature-image {
            display: block;
            margin: 0 auto;
            max-height: 42px;
            max-width: 180px;
            object-fit: contain;
        }

        .sig-label {
            border-top: 1px solid #000;
            font-size: 12px;
            font-weight: bold;
            padding-top: 4px;
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .text-left {
            text-align: left !important;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .highlight-value {
            background: #f0f0f0;
            display: inline;
            font-weight: bold;
            line-height: 1.4;
            padding: 2px 8px;
        }

        .tax-box div {
            font-size: 13px;
            line-height: 16px;
        }

        .tax-box .highlight-value {
            font-size: 13px;
        }

        .bill-details td {
            font-size: 13px;
            line-height: 16px;
            overflow: hidden;
            word-break: break-word;
        }

        .bill-details .highlight-value {
            font-size: 13px;
            white-space: nowrap;
        }

        .words-row .highlight-value {
            font-size: 13px;
        }

        .footer-head .highlight-value {
            font-size: 13px;
        }

        .sig-label {
            font-size: 12.5px;
            letter-spacing: 0.5px;
            padding-top: 4px;
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
                    || $candidates->contains('CUSTOM_ITEM_'.$key)
                    || $candidates->contains('CUSTOM_CUSTOMER_'.$key)
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

    $numberToWords = function ($number) use (&$numberToWords) {
        $number = (int) $number;

        if ($number === 0) {
            return 'Zero';
        }

        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            return trim($tens[intdiv($number, 10)].' '.$ones[$number % 10]);
        }

        if ($number < 1000) {
            $remainder = $number % 100;

            return trim($ones[intdiv($number, 100)].' Hundred'.($remainder ? ' '.$numberToWords($remainder) : ''));
        }

        if ($number < 100000) {
            $remainder = $number % 1000;

            return trim($numberToWords(intdiv($number, 1000)).' Thousand'.($remainder ? ' '.$numberToWords($remainder) : ''));
        }

        if ($number < 10000000) {
            $remainder = $number % 100000;

            return trim($numberToWords(intdiv($number, 100000)).' Lakh'.($remainder ? ' '.$numberToWords($remainder) : ''));
        }

        $remainder = $number % 10000000;

        return trim($numberToWords(intdiv($number, 10000000)).' Crore'.($remainder ? ' '.$numberToWords($remainder) : ''));
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
            'gst_tax_through' => ['tr_gst_through', 'gst_through', 'gst_tax_through', 'service_tax_through'],
            'service_tax_through' => ['tr_gst_through', 'gst_through', 'gst_tax_through', 'service_tax_through'],
            'gst_tax_payable_by' => ['tr_gst_payable_by', 'gst_payable_by', 'gst_tax_payable_by'],
            'gst_payable_by' => ['tr_gst_payable_by', 'gst_payable_by', 'gst_tax_payable_by'],
            'bank' => ['tr_bank', 'tr_advance_bank', 'tr_final_bank', 'bank'],
            'cheque_no' => ['tr_cash_cheque_no', 'tr_advance_cash_cheque_no', 'tr_final_cash_cheque_no', 'cheque_no'],
            'cash' => ['tr_cash_cheque_no', 'cash'],
            'payment_date' => ['tr_advance_on', 'tr_final_balance_on', 'payment_date'],
            'prepared_by' => ['tr_hire_prepared_by', 'tr_final_prepared_by', 'prepared_by'],
            'checked_by' => ['tr_hire_certified_by', 'tr_final_certified_by', 'tr_hire_passed_by', 'tr_final_passed_by', 'checked_by'],
            'gstin' => ['gstin', 'gst_no'],
            'gst_no' => ['gst_no', 'gstin'],
            'pan' => ['pan_no', 'pan'],
            'pan_no' => ['pan_no', 'pan'],
            'enrollment_no' => ['enrollment_no', 'enrollment'],
            'enrollment' => ['enrollment_no', 'enrollment'],
            'party_code' => ['party_code', 'customer_code'],
            'branch_code' => ['branch_code'],
            'tick_bill_type' => ['tick_bill_type', 'bill_type', 'tr_mode_of_payment'],
            'bill_type' => ['tick_bill_type', 'bill_type', 'tr_mode_of_payment'],
            'basis_of_charges' => ['basis_of_charges', 'basis'],
            'enclosures' => ['enclosures', 'tr_received_no_bilties'],
            'emp_code' => ['emp_code', 'employee_code'],
            'mobile' => ['mobile', 'phone', 'tr_consignor_phone'],
            'phone' => ['phone', 'mobile', 'tr_consignor_phone'],
            'email' => ['email'],
            'billing_branch' => ['billing_branch', 'billing_branch_name_address', 'billing_branch_address'],
            'billing_branch_name_address' => ['billing_branch_name_address', 'billing_branch_address', 'billing_branch'],
            'billing_branch_address' => ['billing_branch_address', 'billing_branch_name_address', 'billing_branch'],
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
                    return $camel === 'invoicePdfUrl' ? $invoice->invoicePdfUrl : $invoice->$camel;
                }
            }
        }

        return $fieldValue($invoice->fields ?? [], $keys);
    };

    $customerField = function ($keys) use ($invoice, $fieldValue) {
        return $invoice->customer ? $fieldValue($invoice->customer->fields ?? [], $keys) : '';
    };

    $itemField = function ($item, $keys) use ($fieldValue, $normalize) {
        $aliases = [
            'from' => ['tr_from_name', 'tr_from_code', 'from_name', 'from_code'],
            'to' => ['tr_to_name', 'tr_to_code', 'to_name', 'to_code'],
            'destination' => ['tr_to_name', 'tr_to_code', 'to_name', 'to_code'],
            'vehicle_no' => ['tr_truck_no', 'tr_lorry_no', 'truck_no', 'lorry_no', 'vehicle_no'],
            'vehicle_number' => ['tr_truck_no', 'tr_lorry_no', 'truck_no', 'lorry_no', 'vehicle_number'],
            'truck_no' => ['tr_truck_no', 'tr_lorry_no', 'truck_no', 'lorry_no'],
            'consignment_no' => ['tr_consignment_number', 'consignment_number', 'consignment_no'],
            'consignment_number' => ['tr_consignment_number', 'consignment_number'],
            'old_bill_number' => ['tr_consignment_number', 'consignment_number'],
            'consignment_date' => ['tr_consignment_date', 'consignment_date'],
            'old_bill_date' => ['tr_consignment_date', 'consignment_date'],
            'date' => ['tr_consignment_date', 'consignment_date'],
            'invoice_no' => ['tr_party_inv_no', 'party_inv_no', 'invoice_no'],
            'invoice_number' => ['tr_party_inv_no', 'party_inv_no', 'invoice_number'],
            'party_inv_no' => ['tr_party_inv_no', 'party_inv_no'],
            'pkg' => ['tr_pkg_weight', 'pkg', 'tr_packing', 'packing'],
            'package' => ['tr_pkg_weight', 'pkg', 'tr_packing', 'packing'],
            'packages' => ['tr_pkg_weight', 'pkg', 'tr_packing', 'packing'],
            'weight' => ['tr_charged_weight', 'charged_weight', 'weight'],
            'charged_weight' => ['tr_charged_weight', 'charged_weight', 'weight'],
            'charged_weight_kgs' => ['tr_charged_weight', 'charged_weight', 'weight'],
            'rate' => ['tr_rate', 'rate'],
            'other_charge' => ['tr_other_charge', 'other_charge'],
            'lr_charge' => ['tr_lr_charge', 'lr_charge'],
            'dd_charge' => ['tr_dd_charge', 'dd_charge'],
            'amount' => ['amount', 'total'],
        ];

        foreach ((array) $keys as $rawKey) {
            $normalizedKey = strtolower(trim($rawKey));

            $candidates = $aliases[$normalizedKey] ?? [];
            array_unshift($candidates, $normalizedKey);
            if (! str_starts_with($normalizedKey, 'tr_')) {
                $candidates[] = 'tr_' . $normalizedKey;
            }

            foreach ($candidates as $cand) {
                if (isset($item->$cand) && trim((string) $item->$cand) !== '') {
                    return $item->$cand;
                }
                $camel = \Illuminate\Support\Str::camel($cand);
                if (isset($item->$camel) && trim((string) $item->$camel) !== '') {
                    return $item->$camel;
                }
            }
        }

        return $fieldValue($item->fields ?? [], $keys);
    };

    $companyName = $invoiceField(['company_name']) ?: ($invoice->company?->name ?: '');
    $companyInitials = collect(preg_split('/\s+/', trim($companyName)))
        ->filter()
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->take(2)
        ->implode('');
    $logo = $logo ?? ($invoice->company?->logo_path ?? null);
    // Billing Branch: dynamically read from Address 2nd box (address_street_2),
    // or company billing_branch, or custom invoice field
    $billingBranch = $invoice->company?->address?->address_street_2
        ?: ($invoice->company?->billing_branch
            ?: ($invoiceField(['billing_branch_name_address', 'billing_branch_address', 'billing_branch']) ?: ''));
    $billingBranchHtml = preg_replace('/<br\s*\/?>/i', "\n", (string) $billingBranch);
    $billingBranchHtml = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n", $billingBranchHtml);
    $billingBranchHtml = preg_replace('/<\/?p[^>]*>/i', "\n", $billingBranchHtml);
    $billingBranchText = html_entity_decode(strip_tags($billingBranchHtml), ENT_QUOTES, 'UTF-8');
    $billingBranchLines = collect(preg_split('/\r\n|\r|\n/', $billingBranchText))
        ->map(fn ($line) => trim(preg_replace('/\s+/', ' ', $line)))
        ->filter()
        ->values();
    $companyTagline = $invoice->company?->tagline ?: '';
    // GSTIN: read from VAT Identification Number (vat_id) from /admin/settings/company-info, or custom field, or company gstin
    $companyGstin = $invoiceField(['company_gstin', 'gstin', 'gst_no'])
        ?: ($invoice->company?->vat_id
            ?: ($invoice->company?->gstin ?: ''));
    $companyEnrollmentNo = $invoice->company?->enrollment_no ?: $invoiceField(['enrollment_no', 'enrollment']);
    $companyTaxIdentityLabel = $companyEnrollmentNo ? 'Enrollment No' : 'GSTIN';
    $companyTaxIdentityValue = $companyEnrollmentNo ?: $companyGstin;
    // PAN No: read from Tax Identification Number (tax_id) from /admin/settings/company-info, or custom field, or company pan_no
    $panNo = $invoiceField(['company_pan', 'pan_no', 'pan'])
        ?: ($invoice->company?->tax_id
            ?: ($invoice->company?->pan_no ?: ''));
    $partyGstin = $invoice->customer?->tax_id ?: ($invoiceField(['party_gstin', 'consignor_gst', 'consignor_gst_no', 'gstin', 'gst_no']) ?: $customerField(['gstin', 'gst_no']));
    $partyCode = $invoiceField(['party_code', 'customer_code', 'consignor_code']) ?: ($invoice->customer?->prefix ?: '');
    $branchCode = $invoiceField(['branch_code']);
    $tickBillType = $invoiceField(['tick_bill_type', 'bill_type', 'mode_of_payment', 'tr_mode_of_payment']);
    $basisOfCharges = $invoiceField(['basis_of_charges', 'basis']);
    $enclosures = $invoiceField(['enclosures', 'tr_received_no_bilties']);
    $gstTaxThrough = $invoice->gst_tax_payable_by ?: ($invoiceField(['gst_tax_through', 'service_tax_through', 'gst_tax_payable_by', 'tr_gst_payable_by']) ?: 'CONSIGNOR');

    $empCode = $invoiceField(['emp_code', 'employee_code']);
    $preparedBy = $invoiceField(['prepared_by']);
    $checkedBy = $invoiceField(['checked_by']);
    $billingAddress = $invoice->customer?->billingAddress ?: ($invoice->customer?->shippingAddress ?: $invoice->customer?->addresses?->first());
    $partyDisplayName = $invoiceField(['party_name', 'consignor_name', 'customer_name']) ?: ($billingAddress?->name ?: ($invoice->customer?->display_name ?: $invoice->customer?->name));
    $partyAddressLines = collect();

    if ($billingAddress) {
        $cityState = collect([$billingAddress->city, $billingAddress->state])->filter()->implode(', ');
        $cityStateZip = collect([$cityState, $billingAddress->zip])->filter()->implode(' ');
        $phone = $billingAddress->phone ?: ($invoice->customer?->phone ?? null);

        $partyAddressLines = collect([
            $billingAddress->address_street_1,
            $billingAddress->address_street_2,
            $cityStateZip,
            $billingAddress->country?->name,
            $phone ? 'Phone: ' . $phone : null,
        ])->filter()->values();
    }

    if ($partyAddressLines->isEmpty() && isset($billing_address) && $billing_address) {
        $partyAddressHtml = preg_replace('/<br\s*\/?>/i', "\n", (string) $billing_address);
        $partyAddressHtml = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n", $partyAddressHtml);
        $partyAddressHtml = preg_replace('/<\/?p[^>]*>/i', "\n", $partyAddressHtml);
        $partyAddressText = html_entity_decode(strip_tags($partyAddressHtml), ENT_QUOTES, 'UTF-8');
        $partyAddressLines = collect(preg_split('/\r\n|\r|\n/', $partyAddressText))
            ->map(fn ($line) => trim(preg_replace('/\s+/', ' ', $line)))
            ->reject(fn ($line) => $partyDisplayName && strcasecmp($line, $partyDisplayName) === 0)
            ->filter()
            ->values();

        if (! $partyDisplayName && $partyAddressLines->isNotEmpty()) {
            $partyDisplayName = $partyAddressLines->shift();
        }
    }

    if ($partyAddressLines->isEmpty() && ! empty($invoice->tr_consignor)) {
        $consignorLines = collect(preg_split('/\r\n|\r|\n/', (string) $invoice->tr_consignor))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();
        if (! $partyDisplayName && $consignorLines->isNotEmpty()) {
            $partyDisplayName = $consignorLines->first();
            $partyAddressLines = $consignorLines->slice(1)->values();
        } else {
            $partyAddressLines = $consignorLines;
        }
    }
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
        $isStreet2InBranch = $billingBranch && trim((string) $address?->address_street_2) !== '' && str_contains((string) $billingBranch, trim((string) $address?->address_street_2));
        $displayCompanyAddress = implode('<br>', array_filter([
            e($address?->address_street_1),
            $isStreet2InBranch ? null : e($address?->address_street_2),
            e(trim(implode(' ', array_filter([$address?->city, $address?->state, $address?->zip])))),
            e($address?->country_name),
        ]));
    }
    $officeGrandTotal = 0;
    $signaturePath = base_path('resources/static/img/PDF/authorized_signature.jpeg');
    $userSignatureMedia = auth()->user()?->getMedia('user_signature')->first();
    $userSignaturePath = $userSignatureMedia ? $userSignatureMedia->getPath() : null;

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
    $partyDisplayNameStyle = '';
    $partyGstinStyle = '';
    $companyNameStyle = $getFontForWidth($companyName, 430, 23, 10);
    $branchAddressStyle = '';
@endphp

    <div class="invoice-shell">
        <table class="master">
            <tr>
                <td class="left-zone">
                    <table class="brand-row">
                        <tr>
                            <td class="logo-cell">
                                @if ($logo && file_exists($logo))
                                    <img class="company-logo" src="{{ \App\Platform\Pdf\Rendering\ImageUtils::toBase64Src($logo) }}" alt="Company Logo">
                                @else
                                    <div class="brand-fallback">{{ $companyInitials }}</div>
                                @endif
                            </td>
                            <td class="company-cell">
                                <div class="company-name" style="{{ $companyNameStyle }}">{{ $companyName }}</div>
                                <div class="company-tagline">{{ $companyTagline }}</div>
                                <div class="company-address">{!! $displayCompanyAddress !!}</div>
                                <div class="company-contact">Mob. {{ $mobile }} &nbsp;|&nbsp; E-mail : {{ $email }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="right-zone branch-box">
                    <span class="branch-label">Billing Br. Name & Address :</span>
                    <span class="branch-address">{!! nl2br(e($billingBranchLines->implode("\n"))) ?: '&nbsp;' !!}</span>
                </td>
            </tr>

            <tr>
                <td rowspan="3" class="party-box">
                    <table class="party-head">
                        <tr>
                            <td width="50%"><b>Party Name & Address :</b></td>
                            <td><b>Party Code :</b> {{ $partyCode }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="party-gstin" style="{{ $partyGstinStyle }}"><b>GSTIN :</b> {{ $partyGstin }}</td>
                        </tr>
                    </table>
                    <div class="party-address-lines">
                        <div class="party-display-name" style="{{ $partyDisplayNameStyle }}">{{ $partyDisplayName }}</div>
                        {!! nl2br(e($partyAddressLines->implode("\n"))) ?: "\u{00A0}" !!}
                    </div>
                </td>
                <td class="tax-box">
                    <div>PAN No.: {{ $panNo }}</div>
                    <div>{{ $companyTaxIdentityLabel }} : {{ $companyTaxIdentityValue }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <table class="bill-details">
                        <tr>
                            <td width="50%">Bill No.: {{ $invoice->invoice_number }}</td>
                            <td>Branch Code : {{ $branchCode }}</td>
                        </tr>
                        <tr>
                            <td>Bill Date : {{ $invoice->formattedInvoiceDate }}</td>
                            <td>Due Date : {{ $invoice->formattedDueDate }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>
                    <table class="payment-table">
                        <tr>
                            <th rowspan="2" width="16%">Tick<br>Bill Type<br>{{ $tickBillType }}</th>
                            <th>Cash</th>
                            <th>Cheque No.</th>
                            <th>Date</th>
                            <th>Bank</th>
                            <th>Others</th>
                        </tr>
                        <tr>
                            <td>{{ $invoiceField(['cash']) }}</td>
                            <td>{{ $invoiceField(['cheque_no']) }}</td>
                            <td>{{ $invoiceField(['payment_date']) }}</td>
                            <td>{{ $invoiceField(['bank']) }}</td>
                            <td>{{ $invoiceField(['others']) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="items">
            <colgroup>
                <col style="width: 3%;">
                <col style="width: 7%;">
                <col style="width: 7%;">
                <col style="width: 8%;">
                <col style="width: 6.8%;">
                <col style="width: 6.8%;">
                <col style="width: 9%;">
                <col style="width: 5.5%;">
                <col style="width: 7.4%;">
                <col style="width: 8.5%;">
                <col style="width: 6.8%;">
                <col style="width: 6.8%;">
                <col style="width: 6.8%;">
                <col style="width: 12.2%;">
            </colgroup>
            <thead>
                <tr class="group-head">
                    <th rowspan="2">Sl.<br>No.</th>
                    <th colspan="2">Consignment / Old Bill</th>
                    <th rowspan="2">Invoice<br>No.</th>
                    <th colspan="2">Destination</th>
                    <th rowspan="2">Vehicle No.</th>
                    <th rowspan="2">Pkg.</th>
                    <th rowspan="2">Charged<br>Weight Kgs.</th>
                    <th rowspan="2">Rate</th>
                    <th rowspan="2">Other Charge</th>
                    <th rowspan="2">LR Charge</th>
                    <th rowspan="2">DD Charge</th>
                    <th rowspan="2">Amount</th>
                </tr>
                <tr>
                    <th>Number</th>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $slNo = 0;
                @endphp
                @foreach ($invoice->items as $item)
                    @php
                        $rate = $itemField($item, ['rate']);
                        $otherCharge = $itemField($item, ['other_charge']);
                        $lrCharge = $itemField($item, ['lr_charge']);
                        $ddCharge = $itemField($item, ['dd_charge']);
                        $amount = $itemField($item, ['amount']);

                        // Auto-fit styles for item table cells that commonly overflow.
                        // Column widths: consignment_no 7%≈73px, from 6.8%≈71px,
                        // destination 6.8%≈71px, vehicle_no 9%≈88px, invoice_no 8%≈78px.
                        $consignmentNo = $itemField($item, ['consignment_no', 'consignment_number', 'old_bill_number']);
                        $consignmentDate = $itemField($item, ['consignment_date', 'old_bill_date', 'date']);
                        $partyInvNo = $itemField($item, ['party_inv_no', 'invoice_no', 'invoice_number']);

                        $fromPlace = $itemField($item, ['from']);
                        $toPlace = $itemField($item, ['destination', 'to']);
                        $vehicleNo = $itemField($item, ['vehicle_no', 'vehicle_number']);
                        $pkg = $itemField($item, ['pkg', 'package', 'packages']);
                        $weight = $itemField($item, ['weight', 'charged_weight_kgs', 'charged_weight']);

                        $consignmentNoStyle = $getFontForWidth($consignmentNo, 65, 11.8, 6.5);
                        $fromStyle = $getFontForWidth($fromPlace, 63, 11.8, 6.5);
                        $toStyle = $getFontForWidth($toPlace, 63, 11.8, 6.5);
                        $vehicleNoStyle = $getFontForWidth($vehicleNo, 80, 11.8, 6.5);
                        $partyInvNoStyle = $getFontForWidth($partyInvNo, 70, 11.8, 6.5);

                        $calculatedAmount = null;

                        if ($rate !== '' || $otherCharge !== '' || $lrCharge !== '' || $ddCharge !== '') {
                            $calculatedAmount = (int) round((
                                $numericField($rate)
                                + $numericField($otherCharge)
                                + $numericField($lrCharge)
                                + $numericField($ddCharge)
                            ) * 100);
                        }

                        $officeLineTotal = $calculatedAmount ?? ($amount !== '' ? (int) round($numericField($amount) * 100) : $item->total);
                        $officeGrandTotal += $officeLineTotal;
                        $slNo++;
                    @endphp
                    <tr class="{{ $slNo % 2 === 0 ? 'alt-row' : '' }}">
                        <td>{{ $slNo }}</td>
                        <td style="{{ $consignmentNoStyle }}">{{ $consignmentNo }}</td>
                        <td>{{ $consignmentDate }}</td>
                        <td style="{{ $partyInvNoStyle }}">{{ $partyInvNo }}</td>
                        <td class="text-left" style="{{ $fromStyle }}">{{ $fromPlace }}</td>
                        <td class="text-left" style="{{ $toStyle }}">{{ $toPlace }}</td>
                        <td style="{{ $vehicleNoStyle }}">{{ $vehicleNo }}</td>
                        <td>{{ $pkg }}</td>
                        <td>{{ $weight }}</td>
                        <td class="text-right">{{ $rate }}</td>
                        <td class="text-right">{{ $otherCharge }}</td>
                        <td class="text-right">{{ $lrCharge }}</td>
                        <td class="text-right">{{ $ddCharge }}</td>
                        <td class="text-right">{!! format_money_pdf($officeLineTotal, $invoice->customer?->currency ?: $invoice->company?->currency) !!}</td>
                    </tr>
                @endforeach
            </tbody>

        </table>

        @php
            $grandTotalForWords = $officeGrandTotal ?: $invoice->total;
            $rupeesInWords = $invoiceField(['rupees_in_words', 'amount_in_words']) ?: trim($numberToWords((int) floor($grandTotalForWords / 100)).' Rupees Only');

        @endphp

        <table class="words-row">
            <colgroup>
                <col style="width: 62%;">
                <col style="width: 20%;">
                <col style="width: 18%;">
            </colgroup>
            <tr>
                <td><b>Rupees in words :</b> <span class="highlight-value">{{ $rupeesInWords }}</span></td>
                <td class="grand-label">GRAND TOTAL</td>
                <td class="text-right bold grand-total-value">{!! format_money_pdf($officeGrandTotal ?: $invoice->total, $invoice->customer?->currency ?: $invoice->company?->currency) !!}</td>
            </tr>
        </table>

        <table class="footer">
            <tr class="footer-head">
                <td width="42%" class="bold">Enclosures : {{ $enclosures }}</td>
                <td width="20%" colspan="2"><b>GST Through :</b> <span class="highlight-value">{{ $gstTaxThrough }}</span></td>
                <td width="38%" class="text-center">
                    <div class="for-company">For {{ $companyName }}</div>
                </td>
            </tr>
            <tr class="footer-body">
                <td width="42%" class="terms">
                    <div class="terms-title">Terms &amp; Conditions :</div>
                    <div class="term-item">• Payment should be made by payee A/c Cheque / D.D. in favour of {{ $companyName }}.</div>
                    <div class="term-item">• Interest @ 10% per annum will be charged if bill not paid within 7 days from date of bill.</div>
                </td>

                <td width="10%" class="prepared text-center">Prepared by :<br>{{ $preparedBy }}</td>
                <td width="10%" class="prepared text-center">Checked by :<br>{{ $checkedBy }}</td>
                <td width="38%" class="signature-cell">
                    <table class="signature-table">
                        <tr>
                            <td class="sig-emp">
                                <span class="emp-box">EMP Code<br>{{ $empCode }}</span>
                            </td>
                            <td class="sig-image-area">
                                @if ($userSignaturePath && file_exists($userSignaturePath))
                                    <img class="signature-image" src="{{ \App\Platform\Pdf\Rendering\ImageUtils::toBase64Src($userSignaturePath) }}" alt="Signature">
                                @elseif (file_exists($signaturePath))
                                    <img class="signature-image" src="{{ \App\Platform\Pdf\Rendering\ImageUtils::toBase64Src($signaturePath) }}" alt="Signature">
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="font-size: 13px; font-weight: bold; text-align: center; padding-top: 4px;">
                                {{ auth()->user()?->name ?: $preparedBy }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="sig-label">Signature</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>