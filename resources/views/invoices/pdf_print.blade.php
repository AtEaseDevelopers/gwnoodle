<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $companyInfo['name'] }} - {{ $mode === 'do' ? 'Delivery Order' : 'Invoice' }}</title>
    <style>
        /* Real A4 page. @page margins are unreliable in dompdf, so the
           left/right spacing is applied as body padding instead - centers
           the ~120mm content column on the 210mm-wide page. */
        @page {
            size: A4;
            margin-top: 12mm;
            margin-right: 0;
            margin-bottom: 12mm;
            margin-left: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "dejavu sans", "helvetica", "tahoma", sans-serif;
            margin: 0;
            padding: 0 10mm;
        }

        /* Main invoice container */
        .invoice-container {
            width: 100%;
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table td, table th {
            padding: 2px 0;
            vertical-align: top;
        }

        /* Company header - exactly like PDF */
        .company-header {
            text-align: center;
        }

        .company-name {
            font-weight: bold;
            font-size: 21px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .company-address {
            font-size: 14px;
            margin: 2px 0;
            line-height: 1.3;
        }

        .company-tin {
            font-size: 14px;
            margin: 2px 0;
        }

        /* Invoice title */
        .invoice-title {
            text-align: right;
            font-weight: bold;
            font-size: 25px;
        }

        /* Divider styles */
        .divider-dashed {
            border-top: 1px solid #000;
            margin: 5px 0;
        }

        .divider-solid {
            border-top: 1px solid #000;
            margin: 5px 0;
        }

        /* Invoice info row - two column layout */
        .invoice-info {
            width: 100%;
            margin: 5px 0;
            font-size: 16px;
        }

        .invoice-info td {
            padding: 2px 0;
        }

        .info-value {
            text-align: right;
        }

        /* Customer section - matching PDF */
        .customer-section {
            margin: 8px 0;
            font-size: 16px;
        }

        .customer-name {
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .customer-address {
            margin: 2px 0;
            line-height: 1.3;
        }

        .customer-contact {
            margin: 2px 0;
        }

        /* Items table - exact match to PDF */
        .items-table {
            font-size: 16px;
        }

        /* Repeat the header row and keep each item on one page when the
           table spans multiple pages. */
        .items-table thead {
            display: table-header-group;
        }

        .items-table tbody tr {
            page-break-inside: avoid;
        }

        .items-table th {
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding: 3px 6px 3px 0;
            font-size: 14px;
        }

        .items-table td {
            padding: 2px 6px 2px 0;
            font-size: 16px;
        }

        /* Column widths matching PDF */
        .col-item { width: 8%; }
        .col-description { width: 32%; }
        .col-qty { width: 10%; text-align: center;}
        .col-uom { width: 10%; }
        .col-price { width: 15%; }
        .col-disc { width: 10%; }
        .col-total { width: 15%; text-align: right; }

        /* Delivery order mode - no Disc/Total columns, widen the rest */
        .items-table-do .col-item { width: 8%; }
        .items-table-do .col-description { width: 47%; }
        .items-table-do .col-qty { width: 15%; }
        .items-table-do .col-uom { width: 12%; }
        .items-table-do .col-price { width: 18%; }

        .item-description-sub {
            padding-left: 12px;
            font-size: 14px;
            color: #555;
        }

        /* Total section */
        .total-section {
            margin: 5px 0;
            text-align: right;
        }

        .total-amount {
            font-size: 25px;
            font-weight: bold;
            text-align: right;
        }

        .amount-in-words {
            font-size: 14px;
            margin: 8px 0;
            text-align: left;
        }

        /* Notes section - matching PDF exactly */
        .notes-section {
            font-size: 11px;
            line-height: 1.4;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .note-item {
            margin: 2px 0;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 8px;
            font-size: 13px;
        }

        .page-info {
            text-align: right;
            font-size: 14px;
            margin-top: 5px;
        }

        .invoice-header-grid {
            display: grid;
            grid-template-columns: 70% 30%;
            gap: 10px;
            margin: 5px 0;
        }

        .left-column {
            vertical-align: top;
        }

        .right-column {
            vertical-align: top;
        }

        /* Alternative - Using flexbox */
        .invoice-header-flex {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .flex-left {
            flex: 70%;
        }

        .flex-right {
            flex: 30%;
        }
        .two-column-layout {
            display: grid;
            grid-template-columns: 70% 30%;
            gap: 10px;
            margin: 10px 0;
        }
        /* Smaller font for long invoice numbers */
        .small-text {
            font-size: 11px !important;
        }

        .extra-small-text {
            font-size: 9px !important;
        }

        /* Handle long text wrapping */
        .text-wrap {
            word-wrap: break-word;
            word-break: break-all;
        }

        /* Remove background for print */
        @media print {
            body {
                background-color: white;
                padding: 0;
                margin: 0;
            }
            .invoice-container {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

<div class="invoice-container">
    <!-- Company Header -->
    <div class="company-header">
        <div class="company-name">{{ strtoupper($companyInfo['name']) }}</div>
        <div class="company-address">{{ $companyInfo['address1'] }}</div>
        <div class="company-address">Tel: {{ $companyInfo['phone'] }}</div>
        <div class="company-tin">{{ $companyInfo['ssm'] }} | MSIC CODE:{{ $companyInfo['msic_code'] }}</div>
    </div>

    <div class="divider-dashed"></div>

    <!-- Invoice Header with Two Columns - Using Table -->
    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
        <tr>
            <!-- LEFT COLUMN -->
            <td style="width: 55%; vertical-align: top;">
                <div class="invoice-title" style="font-size: 20px;">
                    {{ $mode === 'do' ? 'DELIVERY ORDER' : 'INVOICE' }}
                </div>
                <div class="customer-section" style="text-align: left;">
                    <div class="customer-name" style="font-size: 14px;">{{ strtoupper($invoice->customer->company ?? $invoice->customer->name ?? 'CUSTOMER') }}</div>
                    <div class="customer-address" style="font-size: 13px; word-wrap: break-word; word-break: break-word; max-width: 100%; line-height: 1.3;">
                        {!! nl2br(e($customer_billing_address ?? '')) !!}
                    </div>
                    <div class="customer-contact" style="font-size: 13px; margin-top: 3px;">
                        TEL : {{ $invoice->customer->phone ?? '-' }} &nbsp;&nbsp;&nbsp;&nbsp; FAX :
                    </div>
                </div>
            </td>

            <!-- RIGHT COLUMN -->
            <td style="width: 45%; vertical-align: top;">
                <table class="invoice-info" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="width: 40%; padding: 2px 5px; font-weight: bold; text-align: right;">No.</td>
                        <td style="width: 3%; padding: 2px 0 2px 0; font-weight: bold; text-align: left;">:</td>
                        <td style="width: 57%; padding: 2px 5px; font-weight: bold; text-align: right; word-break: break-all;
                            @if(strlen($invoice->invoiceno ?? '') > 20) font-size: 11px;
                            @elseif(strlen($invoice->invoiceno ?? '') > 30) font-size: 9px;
                            @endif">
                            {{ $invoice->invoiceno ?? '-' }}
                        </td>
                    </tr>

                    <!-- <tr>
                        <td style="width: 40%; padding: 2px 5px; text-align: right; white-space: nowrap;">Our D/O No.</td>
                        <td style="width: 3%; padding: 2px 0 2px 0; text-align: left;">:</td>
                        <td style="width: 57%; padding: 2px 5px; text-align: right; word-break: break-all;
                            @if(strlen($invoice->do_no ?? '') > 20) font-size: 6px;
                            @elseif(strlen($invoice->do_no ?? '') > 30) font-size: 5px;
                            @endif">
                            {{ $invoice->do_no ?? 'DO' . ($invoice->invoiceno ?? '-') }}
                        </td>
                    </tr> -->
                    <tr>
                        <td style="width: 40%; padding: 2px 5px; text-align: right; white-space: nowrap;">Terms</td>
                        <td style="width: 3%; padding: 2px 0 2px 0; text-align: left;">:</td>
                        <td style="width: 57%; padding: 2px 5px; text-align: right;">
                            @php
                                $terms = [1 => 'C.O.D.', 2 => 'Credit', 3 => 'Online', 4 => 'TNG', 5 => 'Cheque'];
                            @endphp
                            {{ $terms[$invoice->paymentterm] ?? 'C.O.D.' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 40%; padding: 2px 5px; text-align: right; white-space: nowrap;">Date</td>
                        <td style="width: 3%; padding: 2px 0 2px 0; text-align: left;">:</td>
                        <td style="width: 57%; padding: 2px 5px; text-align: right;">{{ $invoice->date ? date('d/m/Y', strtotime($invoice->date)) : '-' }}</td>
                    </tr>
                    <tr>
                        <td style="width: 40%; padding: 2px 5px; text-align: right; white-space: nowrap;">Page</td>
                        <td style="width: 3%; padding: 2px 0 2px 0; text-align: left;">:</td>
                        <td style="width: 57%; padding: 2px 5px; text-align: right;">1 of 1</td>
                    </tr>
                    @if(!empty($invoice->po_number_remark))
                    <tr>
                        <td style="width: 40%; padding: 2px 5px; text-align: right; white-space: nowrap;">PO Number Remark</td>
                        <td style="width: 3%; padding: 2px 0 2px 0; text-align: left;">:</td>
                        <td style="width: 57%; padding: 2px 5px; text-align: right;">{{ $invoice->po_number_remark }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="divider-dashed"></div>

    <!-- Items Table -->
    <table class="items-table @if($mode === 'do') items-table-do @endif">
        <thead>
            <tr>
                <th class="col-item">Item</th>
                <th class="col-description">Description</th>
                <th class="col-qty">Qty</th>
                <th class="col-uom">UOM</th>
                @if($mode !== 'do')
                <th class="col-price">U/Price</th>
                <th class="col-disc">Disc.</th>
                <th class="col-total">Total RM</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @php $counter = 1; $totalCols = $mode !== 'do' ? 7 : 5; @endphp
            @foreach($invoice->invoicedetail as $detail)
            <tr>
                <td class="col-item">{{ $counter++ }}.</td>
                <td class="col-description">{{ $detail->product->name ?? '-' }}({{ $detail->batch->batch_code }})</td>
                <td class="col-qty">{{ number_format($detail->quantity) }}</td>
                <td class="col-uom">{{ $detail->product->uom ?? 'PCS' }}</td>
                @if($mode !== 'do')
                <td class="col-price">{{ number_format($detail->price, 2) }}</td>
                <td class="col-disc">-</td>
                <td class="col-total">{{ number_format($detail->totalprice, 2) }}</td>
                @endif
            </tr>
            @if(!empty($detail->remark))
            <tr>
                <td class="col-item"></td>
                <td class="item-description-sub" colspan="{{ $totalCols - 1 }}">({{ $detail->remark }})</td>
            </tr>
            @endif

            @endforeach
        </tbody>
    </table>

    <div class="divider-dashed"></div>

    @if($mode !== 'do')
    <!-- Remarks -->
    <div class="notes-section" style="font-size: 13px;">
        <div class="notes-title">Remarks :</div>
        <div class="note-item">{{ $invoice->remark ?? '-' }}</div>
    </div>

    <div class="divider-dashed"></div>

    <!-- Amount in Words and Total - Same Row with Border -->
    <table style="width: 100%; border-collapse: collapse; margin: 5px 0;">
        <tr>
            <td style="width: 65%; vertical-align: top;">
                <div class="amount-in-words" style="font-size: 13px; margin: 0; font-weight: normal;">
                    RINGGIT MALAYSIA {{ strtoupper($totalAmountText) }} ONLY
                </div>
            </td>
            <td style="width: 35%; text-align: right;">
                <div style="border: 1px solid #000; padding: 5px 10px; text-align: center;">
                    <div style="font-size: 14px; font-weight: bold;">TOTAL (RM)</div>
                    <div style="font-size: 25px; font-weight: bold;">{{ number_format($totalAmount, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider-dashed"></div>
    @else
    <!-- Remarks -->
    <div class="notes-section" style="font-size: 13px;">
        <div class="notes-title">Remarks :</div>
        <div class="note-item">{{ $invoice->remark ?? '-' }}</div>
    </div>

    <div class="divider-dashed"></div>
    @endif

    <!-- Notes + Signature - kept together so signature doesn't spill to the next page -->
    <div style="page-break-inside: avoid;">
        <div class="notes-section">
            <div class="notes-title">Notes :</div>
            <div class="note-item">1. All cheques should be crossed and made payable to GW NOODLES SDN BHD</div>
            <div class="note-item">2. Goods sold are neither returnable nor refundable. Otherwise a cancellation fee of 20% on purchase price will be imposed.</div>
        </div>

        <!-- Computer Generated Notice -->
        <div style="margin-top: 40px; text-align: center;">
            <span style="font-size: 13px; font-weight: bold;">Computer generated, no signature required</span>
        </div>

        <!-- Bank Info -->
        <div style="margin-top: 15px; font-size: 13px;">
            <strong>Maybank :</strong> 551285084789 GW NOODLES SDN. BHD.
        </div>
    </div>

</div>

</body>
</html>
