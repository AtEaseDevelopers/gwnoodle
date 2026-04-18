<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Daily Sales Report - Grouped by Trip</title>
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
        
        /* Trip section styles */
        .trip-section {
            margin: 20px 0 30px 0;
            page-break-inside: avoid;
        }
        
        .trip-header {
            background-color: #4a5568;
            color: white;
            padding: 10px;
            margin: 15px 0 10px 0;
            border-radius: 5px;
        }
        
        .trip-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .trip-info {
            font-size: 9px;
            color: #e2e8f0;
        }
        
        .trip-summary {
            margin: 10px 0;
            padding: 8px;
            background-color: #edf2f7;
            border-left: 3px solid #4a5568;
        }
        
        .trip-summary-grid {
            display: table;
            width: 100%;
        }
        
        .trip-summary-item {
            display: table-cell;
            text-align: center;
            padding: 5px;
        }
        
        /* Driver display styles */
        .driver-value {
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
            line-height: 1.4;
        }
        
        .driver-list {
            display: inline-block;
            max-width: 400px;
        }
        
        .driver-name {
            display: inline-block;
        }
        
        .driver-count {
            display: inline-block;
            margin-left: 5px;
            font-style: italic;
            color: #666;
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
            width: 16.66%;
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
        
        .payment-summary {
            margin: 15px 0;
            padding: 10px;
            background-color: #ffffff;
            border: 1px solid #020202;
        }
        
        .payment-grid {
            display: table;
            width: 100%;
        }
        
        .payment-item {
            display: table-cell;
            text-align: center;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9px;
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
        
        .sub-section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            padding: 5px;
            background-color: #f0f0f0;
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
        
        .empty-message {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
        
        .page-break {
            page-break-before: always;
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
                    <div class="report-title">DAILY SALES REPORT</div>
                    <div class="report-title" style="font-size: 10px; color: #666;">GROUPED BY TRIP</div>
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
                <td class="detail-label">Sales Date:</td>
                <td class="detail-value">{{ $reportData['report_date'] }}</td>
                <td class="detail-label" style="vertical-align: top;">Driver(s):</td>
                <td class="detail-value driver-value">
                    @php
                        $driverNames = [];
                        if(is_array($reportData['driver_filter_display'] ?? null)) {
                            $driverNames = $reportData['driver_filter_display'];
                        } elseif(isset($reportData['driver_filter_display'])) {
                            $driverNames = [$reportData['driver_filter_display']];
                        }
                        
                        $driverCount = count($driverNames);
                        $driversPerLine = 3;
                        $chunks = array_chunk($driverNames, $driversPerLine);
                    @endphp
                    
                    @if($driverCount > 0)
                        <div class="driver-list">
                            @foreach($chunks as $index => $chunk)
                                {{ implode(', ', $chunk) }}@if(!$loop->last)<br>@endif
                            @endforeach
                            @if($driverCount > 1)
                                <span class="driver-count">(Total: {{ $driverCount }} drivers)</span>
                            @endif
                        </div>
                    @else
                        All Drivers
                    @endif
                 </td>
            </tr>
        </table>

        <!-- Overall Summary Section -->
        <div class="summary-section">
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-label">Total Trips</div>
                    <div class="summary-value">{{ number_format($reportData['summary']['total_trips']) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Invoices</div>
                    <div class="summary-value">{{ number_format($reportData['summary']['total_invoices']) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Customers</div>
                    <div class="summary-value">{{ number_format($reportData['summary']['total_customers']) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Quantity</div>
                    <div class="summary-value">{{ number_format($reportData['summary']['total_quantity']) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Sales</div>
                    <div class="summary-value">RM {{ number_format($reportData['summary']['total_sales'], 2) }}</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Total Paid</div>
                    <div class="summary-value">RM {{ number_format($reportData['summary']['total_paid'], 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Overall Payment Summary -->
        <div class="payment-summary">
            <div class="payment-grid">
                <div class="payment-item"><strong>Cash:</strong> RM {{ number_format($reportData['payment_summary']['cash'], 2) }}</div>
                <div class="payment-item"><strong>Credit:</strong> RM {{ number_format($reportData['payment_summary']['credit'], 2) }}</div>
                <div class="payment-item"><strong>Online:</strong> RM {{ number_format($reportData['payment_summary']['online'], 2) }}</div>
                <div class="payment-item"><strong>TNG:</strong> RM {{ number_format($reportData['payment_summary']['tng'], 2) }}</div>
                <div class="payment-item"><strong>Cheque:</strong> RM {{ number_format($reportData['payment_summary']['cheque'], 2) }}</div>
            </div>
        </div>

        <!-- Product Summary Section (Overall) -->
        <div class="sub-section-title">PRODUCT SALES SUMMARY (ALL TRIPS)</div>
        @if(empty($reportData['products']))
            <div class="empty-message">No products sold on this date</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="35%">Product Description</th>
                        <th width="15%">Product Code</th>
                        <th width="20%">Quantity Sold</th>
                        <th width="25%">Total Sales (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['products'] as $product)
                    <tr>
                        <td class="text-center">{{ $product['no'] }}</td>
                        <td>{{ $product['product_name'] }}</td>
                        <td class="text-center">{{ $product['product_code'] }}</td>
                        <td class="text-right">{{ number_format($product['quantity']) }}</td>
                        <td class="text-right">{{ number_format($product['total_sales'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                        <td class="text-right"><strong>{{ number_format($reportData['summary']['total_quantity']) }}</strong></td>
                        <td class="text-right"><strong>RM {{ number_format($reportData['summary']['total_sales'], 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        @endif

        <!-- Trip Details Section -->
        <div class="sub-section-title">TRIP DETAILS</div>
        @if(empty($reportData['trips']))
            <div class="empty-message">No trips found for this date</div>
        @else
            @foreach($reportData['trips'] as $tripIndex => $trip)
                <div class="trip-section">
                    <div class="trip-header">
                        <div class="trip-title">TRIP #{{ $trip['trip_no'] }}</div>
                        <div class="trip-info">
                            Total Invoices: {{ $trip['summary']['total_invoices'] }} | 
                            Total Customers: {{ $trip['summary']['total_customers'] }} | 
                            Total Quantity: {{ number_format($trip['summary']['total_quantity']) }}
                        </div>
                    </div>

                    <!-- Trip Summary -->
                    <div class="trip-summary">
                        <div class="trip-summary-grid">
                            <div class="trip-summary-item">
                                <strong>Total Sales:</strong><br>
                                RM {{ number_format($trip['summary']['total_sales'], 2) }}
                            </div>
                            <div class="trip-summary-item">
                                <strong>Total Paid:</strong><br>
                                RM {{ number_format($trip['summary']['total_paid'], 2) }}
                            </div>
                            <div class="trip-summary-item">
                                <strong>Outstanding:</strong><br>
                                RM {{ number_format($trip['summary']['total_outstanding'], 2) }}
                            </div>
                            <div class="trip-summary-item">
                                <strong>Payment Methods:</strong><br>
                                Cash: RM {{ number_format($trip['payment_summary']['cash'], 2) }}<br>
                                Credit: RM {{ number_format($trip['payment_summary']['credit'], 2) }}
                            </div>
                        </div>
                    </div>

                    <!-- Invoices for this Trip -->
                    @foreach($trip['invoices'] as $invoice)
                        <table>
                            <thead>
                                <tr style="background-color: #e0e0e0;">
                                    <th colspan="5">Invoice: {{ $invoice['invoice_no'] }} | Customer: {{ $invoice['customer_name'] }} ({{ $invoice['customer_code'] }}) | Payment: {{ $invoice['payment_term'] }} | Driver: {{ $invoice['driver'] }}</th>
                                </tr>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="40%">Product</th>
                                    <th width="15%">Quantity</th>
                                    <th width="15%">Price (RM)</th>
                                    <th width="25%">Total (RM)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice['items'] as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $item['product_name'] }} ({{ $item['product_code'] }})</td>
                                    <td class="text-right">{{ number_format($item['quantity']) }}</td>
                                    <td class="text-right">{{ number_format($item['price'], 2) }}</td>
                                    <td class="text-right">{{ number_format($item['total'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="background-color: #f5f5f5;">
                                    <td></td>
                                    <td colspan="3" class="text-right"><strong>Subtotal:</strong></td>
                                    <td class="text-right"><strong>RM {{ number_format($invoice['total_amount'], 2) }}</strong></td>
                                </tr>
                                <tr style="background-color: #f5f5f5;">
                                    <td></td>
                                    <td colspan="3" class="text-right"><strong>Paid:</strong></td>
                                    <td class="text-right"><strong>RM {{ number_format($invoice['paid_amount'], 2) }}</strong></td>
                                </tr>
                                @if($invoice['outstanding'] > 0)
                                <tr style="background-color: #fff3cd;">
                                    <td></td>
                                    <td colspan="3" class="text-right"><strong>Outstanding:</strong></td>
                                    <td class="text-right"><strong>RM {{ number_format($invoice['outstanding'], 2) }}</strong></td>
                                </tr>
                                @endif
                                @if(!empty($invoice['payment_methods']))
                                <tr>
                                    <td colspan="5" style="background-color: #e8f5e9;">
                                        <strong>Payment Methods:</strong>
                                        @foreach($invoice['payment_methods'] as $method => $amount)
                                            {{ $method }}: RM {{ number_format($amount, 2) }}@if(!$loop->last), @endif
                                        @endforeach
                                    </td>
                                </tr>
                                @endif
                            </tfoot>
                        </table>
                    @endforeach
                </div>
                
                <!-- Add page break between trips (optional) -->
                @if(!$loop->last)
                    <div class="page-break"></div>
                @endif
            @endforeach
        @endif

    </div>

    <div class="footer">
        GW NOODLES SDN BHD - Daily Sales Report (Grouped by Trip) | Page 1
    </div>
</body>
</html>