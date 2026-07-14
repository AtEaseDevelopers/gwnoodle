<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Stock Request Form</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
        }

        .page {
            padding: 10px;
        }

        /* Company header */
        .company-header {
            text-align: center;
            margin-bottom: 6px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 9px;
        }

        /* Document title */
        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 4px 0;
            margin: 8px 0;
            letter-spacing: 1px;
        }

        /* Two-column info table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 3px 5px;
            vertical-align: top;
            font-size: 9.5px;
        }
        .info-label {
            font-weight: bold;
            width: 100px;
            white-space: nowrap;
        }
        .info-colon {
            width: 10px;
            text-align: left;
            padding: 3px 4px;
            font-weight: bold;
        }
        .info-value {
            min-width: 120px;
        }
        .info-divider {
            width: 20px;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .items-table th {
            background-color: #e0e0e0;
            border: 1px solid #000;
            padding: 4px 5px;
            font-size: 9.5px;
            text-align: center;
        }
        .items-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            font-size: 9.5px;
            vertical-align: top;
        }
        .items-table .col-no      { text-align: center; width: 30px; }
        .items-table .col-code    { width: 80px; }
        .items-table .col-desc    { }
        .items-table .col-qty     { text-align: center; width: 50px; }
        .items-table .col-uom     { text-align: center; width: 50px; }
        .items-table .col-amount  { text-align: right; width: 80px; }

        /* Footer */
        .footer-section {
            margin-top: 10px;
        }
        .note-row {
            margin-bottom: 8px;
        }
        .note-label {
            font-weight: bold;
            font-size: 9.5px;
        }
        .note-value {
            font-size: 9.5px;
            border-bottom: 1px solid #000;
            min-height: 16px;
            padding: 2px 0;
        }

        .total-row {
            text-align: right;
            font-weight: bold;
            font-size: 10px;
            margin: 6px 0;
            border-top: 1px solid #000;
            padding-top: 4px;
        }

        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .sig-table td {
            width: 50%;
            padding: 0 20px;
            vertical-align: bottom;
            font-size: 9.5px;
        }
        .sig-line {
            border-top: 1px solid #000;
            padding-top: 3px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- Company Header --}}
    <div class="company-header">
        <div class="company-name">GW NOODLES SDN BHD</div>
        <div class="company-sub">(201601033587)</div>
        <div class="company-sub">23, JALAN SETIA PERNIAGAAN 9, TAMAN SETIA PERNIAGAAN</div>
        <div class="company-sub">TEL: +60167237931</div>
    </div>

    {{-- Title --}}
    <div class="doc-title">STOCK REQUEST FORM</div>

    {{-- Two-column info --}}
    <table class="info-table">
        <tr>
            <td class="info-label">Description</td>
            <td class="info-colon">:</td>
            <td class="info-value">{{ $formData['description'] }}</td>
            <td class="info-divider"></td>
            <td class="info-label">No.</td>
            <td class="info-colon">:</td>
            <td class="info-value">{{ $formData['doc_no'] }}</td>
        </tr>
        <tr>
            <td class="info-label">Ref. Doc. No.</td>
            <td class="info-colon">:</td>
            <td class="info-value">{{ $formData['ref_doc_no'] }}</td>
            <td class="info-divider"></td>
            <td class="info-label">Date</td>
            <td class="info-colon">:</td>
            <td class="info-value">{{ \Carbon\Carbon::parse($formData['date'])->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="info-label">Reason</td>
            <td class="info-colon">:</td>
            <td class="info-value">{{ $formData['reason'] }}</td>
            <td class="info-divider"></td>
            <td class="info-label">From Location</td>
            <td class="info-colon">:</td>
            <td class="info-value">{{ $formData['from_location'] }}</td>
        </tr>
        <tr>
            <td class="info-label">Authorised By</td>
            <td class="info-colon">:</td>
            <td class="info-value">{{ $formData['authorised_by'] }}</td>
            <td class="info-divider"></td>
            <td class="info-label">To Location</td>
            <td class="info-colon">:</td>
            <td class="info-value">{{ $formData['to_location'] }}</td>
        </tr>
    </table>

    {{-- Items table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th class="col-code">Item Code</th>
                <th class="col-desc">Description</th>
                <th class="col-qty">Qty</th>
                <th class="col-uom">UOM</th>
                <th class="col-amount">Amount (RM)</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @forelse($formData['items'] as $item)
                @php $total += (float)($item['amount'] ?? 0); @endphp
                <tr>
                    <td class="col-no">{{ $item['no'] }}</td>
                    <td class="col-code">{{ $item['item_code'] }}</td>
                    <td class="col-desc">{{ $item['description'] }}</td>
                    <td class="col-qty">{{ $item['qty'] }}</td>
                    <td class="col-uom">{{ $item['uom'] }}</td>
                    <td class="col-amount">{{ $item['amount'] !== '' ? number_format((float)$item['amount'], 2) : '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;">No items</td>
                </tr>
            @endforelse
            {{-- Pad to at least 10 rows --}}
            @for($i = count($formData['items']); $i < 10; $i++)
                <tr>
                    <td class="col-no">{{ $i + 1 }}</td>
                    <td class="col-code">&nbsp;</td>
                    <td class="col-desc">&nbsp;</td>
                    <td class="col-qty">&nbsp;</td>
                    <td class="col-uom">&nbsp;</td>
                    <td class="col-amount">&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer-section">
        <div class="note-row">
            <span class="note-label">Note : </span>
            <span class="note-value">{{ $formData['note'] }}</span>
        </div>
        <div class="total-row">
            Total : RM {{ number_format($total, 2) }}
        </div>

        <table class="sig-table">
            <tr>
                
                <td>
                    <div style="height:40px;"></div>
                    <div class="sig-line">Authorised By : {{ $formData['authorised_by'] }}</div>
                </td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>
