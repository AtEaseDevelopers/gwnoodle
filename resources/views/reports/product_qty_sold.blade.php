<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Product Quantity Sold Report - Grouped by Trip</title>
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
            width: 25%;
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
                    <div class="report-title">PRODUCT QUANTITY SOLD REPORT</div>
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

        <!-- Overall Summary Section (no sales/payment figures) -->
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
            </div>
        </div>

        <!-- Product Summary Section (Overall) -->
        <div class="sub-section-title">PRODUCT QUANTITY SUMMARY (ALL TRIPS)</div>
        @if(empty($reportData['products']))
            <div class="empty-message">No products sold on this date</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th width="10%">#</th>
                        <th width="55%">Product Description</th>
                        <th width="15%">Product Code</th>
                        <th width="20%">Quantity Sold</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['products'] as $product)
                    <tr>
                        <td class="text-center">{{ $product['no'] }}</td>
                        <td>{{ $product['product_name'] }}</td>
                        <td class="text-center">{{ $product['product_code'] }}</td>
                        <td class="text-right">{{ number_format($product['quantity']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="text-right"><strong>TOTAL:</strong></td>
                        <td class="text-right"><strong>{{ number_format($reportData['summary']['total_quantity']) }}</strong></td>
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

                    <!-- Invoices for this Trip -->
                    @foreach($trip['invoices'] as $invoice)
                        <table>
                            <thead>
                                <tr style="background-color: #e0e0e0;">
                                    <th colspan="3">Invoice: {{ $invoice['invoice_no'] }} | Customer: {{ $invoice['customer_name'] }} ({{ $invoice['customer_code'] }}) | Driver: {{ $invoice['driver'] }}</th>
                                </tr>
                                <tr>
                                    <th width="10%">#</th>
                                    <th width="65%">Product</th>
                                    <th width="25%">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice['items'] as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>{{ $item['product_name'] }} ({{ $item['product_code'] }})</td>
                                    <td class="text-right">{{ number_format($item['quantity']) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
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
        GW NOODLES SDN BHD - Product Quantity Sold Report (Grouped by Trip) | Page 1
    </div>
</body>
</html>
