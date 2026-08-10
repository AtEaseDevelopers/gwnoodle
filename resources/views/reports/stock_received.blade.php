<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Stock Received Report</title>
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
        
        .filter-info {
            margin: 10px 0;
            padding: 6px;
            background-color: #f5f5f5;
            border-left: 3px solid #4CAF50;
            font-size: 9px;
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
        
        .signature-wrapper {
            text-align: right;  /* Aligns content to the right */
            margin-top: 30px;
            margin-bottom: 20px;
            width: 100%;
        }
        
        .signature-box {
            display: inline-block;  /* Makes it an inline element that respects text-align */
            width: 200px;
            text-align: center;
            margin-left: auto;  /* Pushes to the right */
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
        
        .note-section {
            margin-top: 5px;
            padding: 3px;
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
                    <div class="report-title">STOCK RECEIVED REPORT</div>
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

        @if(isset($filters['product_id']) && $filters['product_id'])
        <div class="filter-info">
            <strong>Product Filter:</strong> {{ \App\Models\Product::find($filters['product_id'])->name ?? 'N/A' }}
        </div>
        @endif

        @if(empty($reportData['items']))
            <div class="empty-message">
                No stock received records found from {{ $reportData['date_from'] }} to {{ $reportData['date_to'] }}
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="30%">Product Description</th>
                        <th width="15%">Product Batch Code.</th>
                        <th width="10%">Warehouse</th>
                        <th width="10%">Expiry Date</th>
                        <th width="10%">Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['items'] as $item)
                        <tr>
                            <td class="text-center">{{ $item['no'] }}</td>
                            <td>
                                {{ $item['product_name'] }}<br>
                                <span style="font-size: 8px; color: #666;">Code: {{ $item['product_code'] }}</span>
                            </td>
                            <td class="text-center">{{ $item['batch_code'] }}</td>
                            <td>{{ $item['warehouse'] }}</td>

                            <td class="text-center">{{ $item['expiry_date'] }}</td>
                            <td class="text-right">{{ number_format($item['quantity']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="5" class="text-right"><strong>TOTAL QUANTITY:</strong></td>
                        <td class="text-right"><strong>{{ number_format($reportData['summary']['total_quantity']) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        @endif

        <div class="note-section">
            <strong>Notes:</strong>
            <ul>
                <li>This report is generated by computer no signature required.</li>
                <li>Prepared by: Warehouse Manager</li>
                <li>Checked by: Supervisor</li>
            </ul>
        </div>

        <div class="signature-wrapper">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Received by</div>
            </div>
        </div>

    </div>

    <div class="footer">
        GW NOODLES SDN BHD - Stock Received Report
    </div>
</body>
</html>