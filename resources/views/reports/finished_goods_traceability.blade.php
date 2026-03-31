<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Finished Goods Traceability Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 10px;
            font-size: 9px;
            line-height: 1.3;
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
        
        .report-subtitle {
            font-size: 9px;
            color: #666;
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
            background-color: #f9f9f9;
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 8px;
        }
        
        th {
            background-color: #a7a7a7;
            color: black;
            padding: 8px 5px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
            font-size: 9px;
        }
        
        td {
            padding: 6px 5px;
            border: 1px solid #ddd;
            vertical-align: top;
            font-size: 8px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .filter-info {
            margin: 10px 0;
            padding: 6px;
            background-color: #e3f2fd;
            border-left: 3px solid #2196f3;
            font-size: 9px;
        }
        
        .total-row {
            background-color: #f0f0f0;
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
        
        .signature-wrapper {
            text-align: right;
            margin-top: 30px;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .signature-box {
            display: inline-block;
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            margin-top: 30px;
            width: 100%;
        }
        
        .signature-label {
            font-size: 9px;
            text-align: center;
            color: #020202;
        }
        
        .note-section {
            margin-top: 15px;
            padding: 6px;
            font-size: 8px;
        }
        
        .note-section ul {
            margin: 3px 0 0 15px;
            padding: 0;
        }
        
        .empty-message {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 11px;
        }
        
        .batch-highlight {
            background-color: #fff3cd;
        }
        
        @page {
            margin: 1cm;
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
                    <div class="report-title">FINISHED GOODS TRACEABILITY</div>
                    <div class="report-subtitle">Delivery Log Report</div>
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
                <td class="detail-label">Date From:</td>
                <td class="detail-value">{{ $reportData['date_from'] }}</td>
                <td class="detail-label">Date To:</td>
                <td class="detail-value">{{ $reportData['date_to'] }}</td>
            </tr>
        </table>

        @if(empty($reportData['items']))
            <div class="empty-message">
                No delivery records found from {{ $reportData['date_from'] }} to {{ $reportData['date_to'] }}
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th width="10%">Delivery Date</th>
                        <th width="8%">Invoice No.</th>
                        <th width="12%">Customer</th>
                        <th width="15%">Product</th>
                        <th width="8%">Quantity</th>
                        <th width="12%">Batch Code</th>
                        <th width="10%">Expiry Date</th>
                        <th width="8%">Warehouse</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['items'] as $item)
                        <tr>
                            <td class="text-center">{{ $item['delivery_date'] }}</td>
                            <td class="text-center">{{ $item['invoice_no'] }}</td>
                            <td>
                                {{ $item['customer_name'] }}<br>
                                <span style="font-size: 7px; color: #666;">Code: {{ $item['customer_code'] }}</span>
                            </td>
                            <td>
                                {{ $item['product_name'] }}<br>
                                <span style="font-size: 7px; color: #666;">Code: {{ $item['product_code'] }}</span>
                            </td>
                            <td class="text-right">{{ number_format($item['quantity']) }}</td>
                            <td class="text-center">
                                <strong>{{ $item['batch_code'] }}</strong>
                            </td>
                            <td class="text-center">{{ $item['expiry_date'] }}</td>
                            <td class="text-center">{{ $item['warehouse'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                        <td class="text-right"><strong>{{ number_format($reportData['summary']['total_quantity']) }}</strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        @endif

        <div class="signature-wrapper">
         
            <div class="signature-box" style="margin-left: 20px;">
                <div class="signature-line"></div>
                <div class="signature-label">Checked By</div>
            </div>
        </div>
    </div>

    <div class="footer">
        GW NOODLES SDN BHD - Finished Goods Traceability Report 
</body>
</html>