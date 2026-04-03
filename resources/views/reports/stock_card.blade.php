<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Stock Card Report</title>
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
            width: 100px;
        }
        
        .detail-value {
            font-size: 10px;
        }
        
        .product-info {
            margin: 15px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        
        .product-info table {
            width: 100%;
        }
        
        .summary-section {
            margin: 15px 0;
            padding: 10px;
            background-color: #d6d6d6;
            border: 1px solid #3b3b3b;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
        }
        
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 8px;
        }
        
        th {
            background-color: #cbcfcb;
            color: black;
            padding: 6px 3px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
            font-size: 8px;
        }
        
        td {
            padding: 5px 3px;
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
        
        .stock-in {
            color: green;
            font-weight: bold;
        }
        
        .stock-out {
            color: red;
            font-weight: bold;
        }
        
        .filter-info {
            margin: 10px 0;
            padding: 6px;
            background-color: #e3f2fd;
            border-left: 3px solid #2196f3;
            font-size: 9px;
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
        
        .empty-message {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 11px;
        }
        .stock-in-text {
            color: green;
            font-weight: bold;
        }

        .stock-out-text {
            color: red;
            font-weight: bold;
        }
        .page-break {
            page-break-before: always;
            margin-top: 20px;
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
                    <div class="report-title">STOCK CARD</div>
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

        <!-- Loop through each product -->
            @foreach($reportData['products'] as $productIndex => $productData)
                @if($productIndex > 0)
                    <div class="page-break"></div>
                @endif
                
                <!-- Product Information -->
                <div class="product-info">
                    <table style="margin: 0;">
                        <tr>
                            <td width="20%"><strong>Item Code:</strong></td>
                            <td width="30%">{{ $productData['product']['code'] }}</td>
                            <td width="20%"><strong>UOM:</strong></td>
                            <td width="30%">{{ $productData['product']['uom'] }}</td>
                        </tr>
                        <tr>
                            <td><strong>Item Description:</strong></td>
                            <td colspan="3">{{ $productData['product']['name'] }}</td>
                        </tr>
                        <tr>
                            <td><strong>Current Stock:</strong></td>
                            <td>{{ number_format($productData['product']['current_stock']) }} {{ $productData['product']['uom'] }}</td>
                            <td><strong>Current Cost:</strong></td>
                            <td>RM {{ number_format($productData['product']['current_cost'], 3) }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Summary Section for this product -->
                <div class="summary-section">
                    <div class="summary-grid">
                        <div class="summary-item">
                            <strong>Opening Balance</strong><br>
                            {{ number_format($productData['summary']['opening_balance']) }}
                        </div>
                        <div class="summary-item">
                            <strong>Stock In</strong><br>
                            {{ number_format($productData['summary']['total_stock_in']) }}
                        </div>
                        <div class="summary-item">
                            <strong>Stock Out</strong><br>
                            {{ number_format($productData['summary']['total_stock_out']) }}
                        </div>
                        <div class="summary-item">
                            <strong>Net Change</strong><br>
                            {{ number_format($productData['summary']['net_change']) }}
                        </div>
                        <div class="summary-item">
                            <strong>Closing Balance</strong><br>
                            {{ number_format($productData['summary']['closing_balance']) }}
                        </div>
                    </div>
                </div>

                @if(empty($productData['transactions']))
                    <div class="empty-message">
                        No transactions found for this product from {{ $reportData['date_from'] }} to {{ $reportData['date_to'] }}
                    </div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th width="3%">#</th>
                                <th width="8%">Date</th>
                                <th width="4%">Type</th>
                                <th width="10%">Van Plate No.</th>
                                <th width="15%">Description</th>
                                <th width="8%">Location</th>
                                <th width="4%">UOM</th>
                                <th width="8%">Batch No.</th>
                                <th width="6%">In/Out Qty</th>
                                <th width="6%">B/F Qty</th>
                                <th width="6%">Unit Cost</th>
                                <th width="8%">Total Cost</th>
                                <th width="8%">BIF Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productData['transactions'] as $transaction)
                            <tr>
                                <td class="text-center">{{ $transaction['no'] }}</td>
                                <td class="text-center">{{ $transaction['date'] }}</td>
                                <td class="text-center">{{ $transaction['type'] }}</td>
                                <td class="text-center">{{ $transaction['lorry_no'] }}</td>
                                <td>{{ $transaction['description'] }}</td>
                                <td>{{ $transaction['location'] }}</td>
                                <td class="text-center">{{ $transaction['uom'] }}</td>
                                <td class="text-center">{{ $transaction['batch_no'] }}</td>
                                <td class="text-right {{ strpos($transaction['in_out_qty'], '+') !== false ? 'stock-in' : 'stock-out' }}">
                                    {{ $transaction['in_out_qty'] }}
                                </td>
                                <td class="text-right">{{ number_format($transaction['bf_qty']) }}</td>
                                <td class="text-right">{{ number_format($transaction['unit_cost'], 3) }}</td>
                                <td class="text-right {{ $transaction['total_cost'] < 0 ? 'stock-out' : 'stock-in' }}">
                                    {{ $transaction['total_cost'] < 0 ? '-' : '' }}RM {{ number_format(abs($transaction['total_cost']), 2) }}
                                </td>
                                <td class="text-right">RM {{ number_format($transaction['bal_cost'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #f0f0f0; font-weight: bold;">
                                <td colspan="8" class="text-right"><strong>TOTAL:</strong></td>
                                <td class="text-right">
                                    <strong>
                                        <span style="color: green;">+{{ number_format($productData['summary']['total_stock_in']) }}</span> / 
                                        <span style="color: red;">-{{ number_format($productData['summary']['total_stock_out']) }}</span>
                                    </strong>
                                </td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
                
                @if(!$loop->last)
                    <div style="margin-top: 20px; border-top: 1px dashed #ccc;"></div>
                @endif
            @endforeach


        <div class="signature-wrapper">
            <div class="signature-box" style="margin-left: 20px;">
                <div class="signature-line"></div>
                <div class="signature-label">Verified by</div>
            </div>
        </div>
    </div>

    <div class="footer">
        GW NOODLES SDN BHD - Stock Card Report | Page 1 of 1
    </div>
</body>
</html>