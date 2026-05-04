<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{config('app.name')}}</title>
    <style>
        @page {
    margin: 0;
    size: auto;
}
html {
    margin: 0;
    padding: 0;
}
body {
    page-break-inside: avoid;
    font-size: 22px;
    font-family: 'Courier New', Courier, monospace;
    line-height: 1.3;
    margin: 0 10px;
    padding: 0;
    width: auto;
}
.receipt-container {
    width: 100%;
}
table{
    width: 100%;
    border-collapse: collapse;
}
table td, table th{
    padding: 2px 0;
    vertical-align: top;
}
.company{
    font-weight: bold;
    text-align: center;
    font-size: 18px;
    margin: 0 0 2px 0;
    text-transform: uppercase;
    width: 100%;
}
.address{
    text-align: center;
    font-size: 18px;
    margin: 1px 0;
    white-space: normal;
    word-break: break-word;
    width: 100%;
}
.header-line{
    text-align: center;
    margin: 5px 0;
    font-size: 18px;
    width: 100%;
}
.divider{
    border-top: 1px dashed #000;
    margin: 8px 0;
    width: 100%;
}
.divider-solid{
    border-top: 1px solid #000;
    margin: 8px 0;
    width: 100%;
}
.ta-r{
    text-align: right;
}
.ta-l{
    text-align: left;
}
.ta-c{
    text-align: center;
}
p{
    margin: 0;
}
.item-row td{
    padding: 1px 0;
    font-size: 14px;
}
.item-name{
    font-size: 14px;
    padding-left: 5px;
    color: #555;
}
.totals td{
    padding: 3px 0;
    font-size: 18px;
    font-weight: bold;

}
.address-block{
    font-size: 16px;
    margin: 2px 0;
    white-space: normal;
    word-wrap: break-word;
    text-align: center;
}
.address-label{
    font-weight: bold;
    font-size: 16px;
    text-transform: uppercase;
    display: block;
    margin-bottom: 2px;
}
.document-info {
    width: 100%;
    margin: 5px 0;
    font-size: 18px;
}
.document-info td {
    padding: 1px 0;
}
.items-summary {
    width: 100%;
    margin: 5px 0;
}
.items-summary td {
    padding: 2px 0;
}
.itemize-header {
    font-weight: bold;
    margin: 10px 0 5px 0;
    text-align: left;
    text-decoration: underline;
}
.total-count {
    text-align: right;
    margin: 5px 0;
    font-weight: normal;
}
.payment-section {
    width: 100%;
    margin: 8px 0;
}
.payment-section td {
    padding: 2px 0;
}
.footer-section {
    margin-top: 10px;
    text-align: center;
}
    </style>
</head>
<body>
    @php
        $totalamount = 0;
        $totalquantity = 0;

        foreach ($invoice['invoicedetail'] as $invoicedetail) {
            $totalamount += $invoicedetail['totalprice'];
            $totalquantity += $invoicedetail['quantity'];
        }
    @endphp

    <div class="receipt-container">
        <!-- Company Header -->
        <div class="company">{{ config('invoice.name') }}</div>
        <div class="address">{{  config('invoice.ssm') }}</div>
        <div class="address">{{  config('invoice.address1') }}</div>
        <div class="address">{{ config('invoice.address2') }}</div>
        <div class="address">{{ env('INVOICE_ADDRESS3') }}</div>
        <div class="address">(TEL){{ config('invoice.phone') ?? '+60167237931' }}</div>

        <div class="divider"></div>

        <!-- Sale Type -->
        <div class="header-line">
            @php
                $paymentMethods = ['1' => 'Cash Sale', '2' => 'Credit Sale', '3' => 'Online BankIn', '4' => 'E-wallet', '5' => 'Cheque'];
            @endphp
            <strong>{{ $paymentMethods[$invoice['paymentterm']] ?? 'Sale' }}</strong>
        </div>

        <!-- Document Info -->
        <table class="document-info">
            <tr>
                <td class="ta-l">Document #:</td>
                <td class="ta-r">{{ $invoice['invoiceno'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="ta-l">Date :</td>
                <td class="ta-r">{{ date_format(date_create($invoice['date']),'D d/M/Y ') ?? '-' }}</td>
            </tr>
            <tr>
                <td class="ta-l">S/Driver :</td>
                <td class="ta-r">{{ $invoice['driver']['name'] ?? '-' }}</td>
            </tr>
            
            @if($invoice['paymentterm']==5 && !empty($invoice['chequeno']))
            <tr>
                <td class="ta-l">Cheque No :</td>
                <td class="ta-r">{{ $invoice['chequeno'] }}</td>
            </tr>
            @endif
        </table>

        <div class="divider"></div>

        <!-- Billing Address -->
        <div class="address-label">BILLING TO :</div>
        <div class="address-block">
            {{ $invoice->customer['company'] ?? '' }}<br>
            ({{ $invoice->customer['phone'] ?? '' }})<br>
            {{ $invoice->customer['billing_address'] ?? 'No billing address provided' }}
        </div>

        <div style="margin-top: 8px;"><span class="address-label">DELIVERY TO :</span></div>
        <div class="address-block">
            {{ $invoice->customer['delivery_address'] ?? 'No delivery address provided' }}
        </div>

        <div class="divider"></div>

        <!-- Items Summary -->
        <table class="items-summary">
            <tr>
                <td class="ta-l">total {{ count($invoice['invoicedetail']) }} items :</td>
                <td class="ta-r">{{ number_format($totalamount, 2) }}</td>
            </tr>
        </table>

        <div class="divider-solid"></div>

        <!-- Items Detail -->
        <div class="itemize-header">Items :</div>
        
        <table style="margin-bottom: 5px;">
            <thead>
                <tr style="font-weight: bold;">
                    <td class="ta-l">Price</td>
                    <td class="ta-r">Qty</td>
                    <td class="ta-r">Amount</td>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice['invoicedetail'] as $invoicedetail)

                <tr class="item-row">
                    <td class="ta-l" style="width: 25%;">{{ number_format($invoicedetail['price'], 2) }}</td>
                    <td class="ta-r" style="width: 30%;">{{ $invoicedetail['quantity'] }} {{ $invoicedetail['uom'] ?? 'UNIT' }}</td>
                    <td class="ta-r" style="width: 20%;">{{ number_format($invoicedetail['totalprice'], 2) }}</td>
                </tr>
                <tr class="item-row">
                    <td colspan="3" class="item-name">{{ $invoicedetail['product']['name'] }}({{$invoicedetail->batch->batch_code }})</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="divider"></div>
        <table>
            </tbody>
                <tr class="item-row">
                    <td class="ta-l" style="width: 25%;"></td>
                    <td class="ta-r" style="width: 30%;">{{ $totalquantity }}</td>
                    <td class="ta-r" style="width: 20%;">{{ number_format($totalamount, 2) }}</td>
                </tr>
            </tbody>
        </table>
        <div class="divider"></div>

        <!-- Totals -->
        <table class="totals">
            <tr>
                <td class="ta-r">Total :</td>
                <td class="ta-r" style = "font-size: 20px;">RM {{ number_format($totalamount, 2) }}</td>
            </tr>
            
        </table>

        <div class="divider-solid"></div>

        <!-- Payments -->
        <table class="payment-section">
            <tr>
                <td class="ta-l">Payments :</td>
                <td class="ta-r">Cash {{ number_format($totalamount, 2) }}</td>
            </tr>
         
        </table>

        <div class="divider"></div>
    </div>
</body>
</html>