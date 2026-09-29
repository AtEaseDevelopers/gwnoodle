<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sales Analysis Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 10px;
            font-size: 10px;
            line-height: 1.4;
        }

        .report-container {
            width: 100%;
        }

        .report-header {
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header-top {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .company-section {
            display: table-cell;
            text-align: left;
            vertical-align: top;
        }

        .title-section {
            display: table-cell;
            text-align: right;
            vertical-align: top;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 8px;
            color: #666;
            line-height: 1.3;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .report-details {
            width: 100%;
            margin: 15px 0;
            border: 1px solid #ddd;
            border-collapse: collapse;
        }

        .report-details td {
            padding: 6px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .detail-label {
            font-weight: bold;
            font-size: 9px;
            color: #666;
            width: 80px;
        }

        .detail-value {
            font-size: 10px;
        }

        .summary-section {
            margin: 15px 0;
            padding: 10px;
            background-color: #f7f6f6;
            border: 1px solid #ddd;
        }

        .summary-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .summary-card {
            display: table-cell;
            width: 20%;
            padding: 5px;
            text-align: center;
            border-right: 1px solid #ddd;
        }

        .summary-label {
            font-size: 9px;
            color: #666;
        }

        .summary-value {
            font-size: 12px;
            font-weight: bold;
            color: #333;
        }

        .customer-section {
            margin: 20px 0 25px 0;
            page-break-inside: avoid;
        }

        .customer-header {
            background-color: #4a5568;
            color: white;
            padding: 8px 10px;
            margin: 15px 0 8px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9px;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th {
            background-color: #c2c2c2;
            color: black;
            padding: 6px 4px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        td {
            padding: 5px 4px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .nowrap {
            white-space: nowrap;
        }

        .subtotal-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .total-row {
            background-color: #d9d9d9;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            margin-top: 15px;
        }

        .empty-message {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }

        @page {
            margin: 1.2cm;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <div class="header-top">
                <div class="company-section">
                    <div class="company-name">GW NOODLES SDN BHD</div>
                    <div class="company-details">
                        20160103358204528D<br>
                        23 JLN SETIA PERNIAGAAN 9, 81100 JOHOR BARU MALAYSIA<br>
                        Tel: 016-723-7931<br>
                        TIN: C24694011050
                    </div>
                </div>
                <div class="title-section">
                    <div class="report-title">SALES ANALYSIS REPORT</div>
                </div>
            </div>
        </div>

        <table class="report-details">
            <tr>
                <td class="detail-label">Document No.:</td>
                <td class="detail-value">{{ $reportData['report_no'] ?? 'N/A' }}</td>
                <td class="detail-label">Generated on:</td>
                <td class="detail-value">{{ $reportData['generated_at'] }}</td>
            </tr>
            <tr>
                <td class="detail-label">Date Range:</td>
                <td class="detail-value" colspan="3">{{ $reportData['report_date'] }}</td>
            </tr>
        </table>

        <div class="summary-section">
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-label">Total Customers</div>
                    <div class="summary-value">{{ number_format($reportData['summary']['total_customers']) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Invoices</div>
                    <div class="summary-value">{{ number_format($reportData['summary']['total_invoices']) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Lines</div>
                    <div class="summary-value">{{ number_format($reportData['summary']['total_lines']) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Quantity</div>
                    <div class="summary-value">{{ number_format($reportData['summary']['total_quantity']) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Sales (RM)</div>
                    <div class="summary-value">{{ number_format($reportData['summary']['total_amount'], 2) }}</div>
                </div>
            </div>
        </div>

        @if(empty($reportData['customers']))
            <div class="empty-message">No sales found for the selected filters</div>
        @else
            @foreach($reportData['customers'] as $customer)
                <div class="customer-section">
                    <div class="customer-header">{{ $customer['customer_name'] }}</div>
                    <table>
                        <thead>
                            <tr>
                                <th width="4%">#</th>
                                <th width="9%">Date</th>
                                <th width="12%">Invoice No</th>
                                <th width="12%" class="nowrap">Product Code</th>
                                <th width="27%">Product Name</th>
                                <th width="7%">UOM</th>
                                <th width="9%">Qty</th>
                                <th width="10%">Unit Price (RM)</th>
                                <th width="10%">Total Price (RM)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customer['lines'] as $i => $line)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td class="nowrap">{{ $line['date'] }}</td>
                                <td class="nowrap">{{ $line['invoiceno'] }}</td>
                                <td class="nowrap">{{ $line['product_code'] }}</td>
                                <td>{{ $line['product_name'] }}</td>
                                <td class="text-center">{{ $line['uom'] }}</td>
                                <td class="text-right">{{ number_format($line['quantity']) }}</td>
                                <td class="text-right">{{ number_format($line['unit_price'], 2) }}</td>
                                <td class="text-right">{{ number_format($line['total_price'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="subtotal-row">
                                <td colspan="6" class="text-right"><strong>Subtotal:</strong></td>
                                <td class="text-right"><strong>{{ number_format($customer['subtotal_quantity']) }}</strong></td>
                                <td></td>
                                <td class="text-right"><strong>{{ number_format($customer['subtotal_amount'], 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endforeach

            <table>
                <tfoot>
                    <tr class="total-row">
                        <td width="4%"></td>
                        <td width="9%"></td>
                        <td width="12%"></td>
                        <td width="12%"></td>
                        <td width="27%" class="text-right"><strong>GRAND TOTAL:</strong></td>
                        <td width="7%"></td>
                        <td width="9%" class="text-right"><strong>{{ number_format($reportData['summary']['total_quantity']) }}</strong></td>
                        <td width="10%"></td>
                        <td width="10%" class="text-right"><strong>{{ number_format($reportData['summary']['total_amount'], 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        @endif

    </div>

    <div class="footer">
        GW NOODLES SDN BHD - Sales Analysis Report | Page 1
    </div>
</body>
</html>
