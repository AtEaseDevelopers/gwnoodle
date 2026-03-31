<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Stock Balance Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 10px;
            font-size: 10px;
            line-height: 1.4;
        }
        
        .report-header {
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            position: relative;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .company-section {
            text-align: left;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-reg {
            font-size: 9px;
            color: #666;
        }
        
        .info-section {
            text-align: right;
        }
        
        .report-date, .user-info {
            font-size: 9px;
            color: #666;
            margin-bottom: 3px;
        }
        
        .report-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .filters-info {
            font-size: 9px;
            background-color: #f5f5f5;
            padding: 5px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }
        
        .summary-section {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            padding: 8px;
            background-color: #f9f9f9;
        }
        
        .summary-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11px;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        
        .summary-card {
            display: table-cell;
            width: 25%;
            padding: 5px;
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
        
        .warehouse-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .warehouse-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            padding: 5px;
            background-color: #e3f2fd;
            border-left: 3px solid #2196f3;
        }
        
        .warehouse-summary {
            font-size: 9px;
            margin-bottom: 8px;
            padding: 4px;
            background-color: #f5f5f5;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9px;
        }
        
        th {
            background-color: #d4d4d4;
            color: black;
            padding: 5px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        
        td {
            padding: 4px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .batch-details {
            margin: 0;
            padding-left: 8px;
        }
        
        .batch-item {
            margin-bottom: 2px;
            font-size: 8px;
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
            margin-top: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .zero-stock {
            color: #999;
            font-style: italic;
        }
        
        @page {
            margin: 1cm;
            footer: html_footer;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="header-top">
            <div class="company-section">
                <div class="company-name">GW NOODLES SDN BHD</div>
            </div>
            <div class="info-section">
                <div class="report-date">Date: {{ $reportData['generated_at'] }}</div>
            </div>
        </div>
        <div class="report-title">STOCK BALANCE REPORT</div>
    </div>

    @if(!empty($filters) && (isset($filters['warehouse_id']) || isset($filters['product_id']) || isset($filters['batch_no'])))
    <div class="filters-info">
        <strong>Applied Filters:</strong><br>
        @if(isset($filters['warehouse_id']) && $filters['warehouse_id'])
            Warehouse: {{ \App\Models\Warehouse::find($filters['warehouse_id'])->name ?? 'N/A' }}<br>
        @endif
        @if(isset($filters['product_id']) && $filters['product_id'])
            Product: {{ \App\Models\Product::find($filters['product_id'])->name ?? 'N/A' }}<br>
        @endif
        @if(isset($filters['batch_no']) && $filters['batch_no'])
            Batch No: {{ $filters['batch_no'] }}<br>
        @endif
        @if(isset($filters['show_zero_stock']) && $filters['show_zero_stock'])
            Showing zero stock items: Yes
        @endif
    </div>
    @endif

    <div class="summary-section">
        <div class="summary-title">Report Summary</div>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Total Warehouses</div>
                <div class="summary-value">{{ $reportData['summary']['warehouses_count'] }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Products with Stock</div>
                <div class="summary-value">{{ $reportData['summary']['total_products'] }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Stock Quantity</div>
                <div class="summary-value">{{ number_format($reportData['summary']['total_quantity']) }}</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Stock Value</div>
                <div class="summary-value">RM {{ number_format($reportData['summary']['total_value'], 2) }}</div>
            </div>
        </div>
    </div>

    @foreach($reportData['warehouses'] as $warehouseIndex => $warehouseData)
        <div class="warehouse-section">
            <div class="warehouse-title">
                {{ $warehouseData['warehouse']['name'] }} ({{ $warehouseData['warehouse']['location'] ?? 'No location' }})
            </div>
            <div class="warehouse-summary">
                <strong>Summary:</strong> {{ $warehouseData['product_count'] }} products | 
                Total Quantity: {{ number_format($warehouseData['total_quantity']) }} | 
                Total Value: RM {{ number_format($warehouseData['total_value'], 2) }}
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Item Description</th>
                        <th>Location</th>
                        <th>Product Batch Code</th>
                        <th>Qty</th>
                        <th>Avg. Cost</th>
                        <th>Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $productCounter = 0;
                    @endphp
                    
                    @foreach($warehouseData['products'] as $product)
                        @php
                            $batchCount = count($product['batches']);
                            $productCounter++;
                        @endphp
                        
                        @foreach($product['batches'] as $batchIndex => $batch)
                            <tr>
                                @if($batchIndex == 0)
                                    <td rowspan="{{ $batchCount }}">{{ $product['product_code'] }}</td>
                                    <td rowspan="{{ $batchCount }}">{{ $product['product_name'] }}</td>
                                    <td rowspan="{{ $batchCount }}">{{ $warehouseData['warehouse']['location'] ?? 'HQ' }}</td>
                                @endif
                                
                                <td>{{ $batch['batch_no'] }}</td>
                                
                                <td class="text-right">{{ number_format($batch['quantity']) }}</td>
                                
                                @if($batchIndex == 0)
                                    <td rowspan="{{ $batchCount }}" class="text-right">
                                        RM {{ number_format($product['average_cost'], 2) }}
                                    </td>
                                    <td rowspan="{{ $batchCount }}" class="text-right">
                                        RM {{ number_format($product['total_value'], 2) }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        
                        @if($product['total_quantity'] == 0 && isset($filters['show_zero_stock']) && $filters['show_zero_stock'])
                            <tr class="zero-stock">
                                <td>{{ $product['product_code'] }}</td>
                                <td>{{ $product['product_name'] }}</td>
                                <td>{{ $warehouseData['warehouse']['location'] ?? 'HQ' }}</td>
                                <td>N/A</td>
                                <td class="text-right">0</td>
                                <td class="text-right">0</td>
                                <td class="text-right">RM 0.000</td>
                                <td class="text-right">RM 0.000</td>
                            </tr>
                        @endif
                    @endforeach
                    
                    @if(empty($warehouseData['products']))
                        <tr>
                            <td colspan="10" class="text-center">No products found with stock in this warehouse</td>
                        </tr>
                    @endif
                </tbody>
                @if(!empty($warehouseData['products']))
                <tfoot>
                    <tr style="background-color: #f0f0f0; font-weight: bold;">
                        <td colspan="4" class="text-right"><strong>Warehouse Total:</strong></td>
                        <td class="text-right"><strong>{{ number_format($warehouseData['total_quantity']) }}</strong></td>
                        <td></td>
                        <td class="text-right"><strong>RM {{ number_format($warehouseData['total_value'], 2) }}</strong></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        
        @if(($warehouseIndex + 1) % 3 == 0 && !$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    <htmlpagefooter name="html_footer">
        <div class="footer">
            Page {PAGENO} of {nbpg} | Generated on: {{ $reportData['generated_at'] }}
        </div>
    </htmlpagefooter>
</body>
</html>