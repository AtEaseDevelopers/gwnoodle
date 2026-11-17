<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Stock Transfer Report</title>
    <style>
        body { font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif; margin: 0; padding: 10px; font-size: 9px; line-height: 1.3; }
        .report-header { margin-bottom: 15px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header-top { display: table; width: 100%; }
        .company-section { display: table-cell; text-align: left; vertical-align: top; }
        .title-section { display: table-cell; text-align: right; vertical-align: top; }
        .company-name { font-size: 14px; font-weight: bold; margin-bottom: 5px; }
        .company-details { font-size: 8px; color: #666; line-height: 1.3; }
        .report-title { font-size: 14px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .report-subtitle { font-size: 9px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 8px; }
        th { background-color: #a7a7a7; color: black; padding: 8px 5px; text-align: left; border: 1px solid #ddd; font-weight: bold; font-size: 9px; }
        td { padding: 6px 5px; border: 1px solid #ddd; vertical-align: top; font-size: 8px; }
        .report-details td { padding: 6px; font-size: 10px; }
        .detail-label { font-weight: bold; font-size: 9px !important; color: #666; width: 80px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background-color: #f0f0f0; font-weight: bold; }
        .empty-message { text-align: center; padding: 30px; color: #999; font-size: 11px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 8px; color: #666; border-top: 1px solid #ddd; padding-top: 5px; }
        @page { margin: 1cm; }
    </style>
</head>
<body>
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
                <div class="report-title">STOCK TRANSFER REPORT</div>
                <div class="report-subtitle">Location to Location Transfer Log</div>
            </div>
        </div>
    </div>

    <table class="report-details">
        <tr>
            <td class="detail-label">From Location:</td>
            <td>{{ $reportData['from_location'] }}</td>
            <td class="detail-label">To Location:</td>
            <td>{{ $reportData['to_location'] }}</td>
        </tr>
        <tr>
            <td class="detail-label">Date From:</td>
            <td>{{ $reportData['date_from'] }}</td>
            <td class="detail-label">Date To:</td>
            <td>{{ $reportData['date_to'] }}</td>
        </tr>
        <tr>
            <td class="detail-label">Generated on:</td>
            <td colspan="3">{{ $reportData['generated_at'] }}</td>
        </tr>
    </table>

    @if(empty($reportData['items']))
        <div class="empty-message">
            No stock transfers found from {{ $reportData['date_from'] }} to {{ $reportData['date_to'] }}
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th width="11%">Transfer Date</th>
                    <th width="10%">From Location</th>
                    <th width="10%">To Location</th>
                    <th width="22%">Product</th>
                    <th width="17%">Batch Code</th>
                    <th width="9%">Expiry Date</th>
                    <th width="8%">Quantity</th>
                    <th width="13%">Transferred By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['items'] as $item)
                    <tr>
                        <td class="text-center">{{ $item['date'] }}</td>
                        <td class="text-center">{{ $item['from_location'] }}</td>
                        <td class="text-center">{{ $item['to_location'] }}</td>
                        <td>
                            {{ $item['product_name'] }}<br>
                            <span style="font-size: 7px; color: #666;">Code: {{ $item['product_code'] }}</span>
                        </td>
                        <td class="text-center"><strong>{{ $item['batch_code'] }}</strong></td>
                        <td class="text-center">{{ $item['expiry_date'] }}</td>
                        <td class="text-right">{{ number_format($item['quantity']) }}</td>
                        <td>{{ $item['user'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="6" class="text-right">TOTAL ({{ count($reportData['items']) }} transfers):</td>
                    <td class="text-right">{{ number_format($reportData['total_quantity']) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer">GW NOODLES SDN BHD - Stock Transfer Report</div>
</body>
</html>
