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
        body{
            page-break-inside: avoid;
            font-size: 12px;
            font-family: 'Courier New', Courier, monospace;
            line-height: 1.3;
            margin: 0 8px;
            padding: 0;
            width: auto;
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
            font-size: 14px;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            width: 100%;
        }
        .address{
            text-align: center;
            font-size: 10px;
            margin: 1px 0;
            white-space: nowrap;
            width: 100%;
        }
        .header-line{
            text-align: center;
            margin: 5px 0;
            font-size: 11px;
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
        .payment-row td{
            padding: 3px 0;
            font-size: 11px;
        }
        .totals td{
            padding: 3px 0;
            font-size: 12px;
            font-weight: bold;
        }
        .barcode{
            text-align: center;
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            margin: 8px 0 5px 0;
            letter-spacing: 2px;
        }
        .small-text{
            font-size: 9px;
            text-align: center;
            color: #666;
            margin: 2px 0;
        }
        .address-block{
            font-size: 10px;
            margin: 2px 0;
            white-space: normal;
            word-wrap: break-word;
            text-align: center;
        }
        .address-label{
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .document-info {
            width: 100%;
            margin: 5px 0;
        }
        .document-info td {
            padding: 1px 0;
        }
        .invoice-summary {
            width: 100%;
            margin: 5px 0;
            background-color: #f9f9f9;
            padding: 5px 0;
        }
        .invoice-header {
            font-weight: bold;
            margin: 10px 0 5px 0;
            text-align: left;
            text-decoration: underline;
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
        .receipt-container {
            width: 100%;
            margin: 0 auto;
        }
        .invoice-item {
            border-bottom: 1px dotted #ccc;
            padding: 3px 0;
        }
        .invoice-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 3px;
            background-color: #e8f5e8;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Company Header -->
        <div class="company">{{ config('invoice.name') }}</div>
        <div class="address">{{ config('invoice.ssm') }}</div>
        <div class="address">{{ config('invoice.address1') }}</div>
        <div class="address">{{ config('invoice.address2') }}</div>
        <div class="address">{{ env('INVOICE_ADDRESS3') }}</div>
        <div class="address">(TEL){{ config('invoice.phone') ?? '+60167237931' }}</div>

        <div class="divider"></div>

        <!-- Receipt Type -->
        <div class="header-line">
            <strong>PAYMENT RECEIPT</strong>
        </div>

        <!-- Document Info -->
        <table class="document-info">
            <tr>
                <td class="ta-l">Receipt No :</td>
                <td class="ta-r">{{ sprintf('PR%05d', $invoice->id) }}</td>
            </tr>
            <tr>
                <td class="ta-l">Date :</td>
                <td class="ta-r">{{ date_format(date_create($invoice['updated_at']), 'D d/M/Y H:i') ?? '-' }}</td>
            </tr>
            <tr>
                <td class="ta-l">Payment Method :</td>
                <td class="ta-r">
                    @if($invoice['type']==1)
                        Cash
                    @elseif($invoice['type']==3)
                        Online BankIn
                    @elseif($invoice['type']==4)
                        E-wallet
                    @elseif($invoice['type']==5)
                        Cheque
                    @endif
                </td>
            </tr>
            
            @if($invoice['type']==5 && !empty($invoice['chequeno']))
            <tr>
                <td class="ta-l">Cheque No :</td>
                <td class="ta-r">{{ $invoice['chequeno'] }}</td>
            </tr>
            @endif
            
            <tr>
                <td class="ta-l">Approved By :</td>
                <td class="ta-r">{{ $invoice->approve_by ?? '-' }}</td>
            </tr>
            
            @if($invoice->status == 1)
            <tr>
                <td class="ta-l">Status :</td>
                <td class="ta-r"><span class="status-badge">COMPLETED</span></td>
            </tr>
            @endif
        </table>

        <div class="divider"></div>

        <!-- Invoices Paid Section -->
        <div class="invoice-header">INVOICES PAID :</div>
        
        <div class="invoice-summary">
            @if(isset($invoice->details) && count($invoice->details) > 0)
                <table style="margin-bottom: 5px;">
                    <thead>
                        <tr style="font-weight: bold; border-bottom: 1px solid #000;">
                            <td class="ta-l">Invoice No.</td>
                            <td class="ta-l">Date</td>
                            <td class="ta-r">Amount (RM)</td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->details as $detail)
                        <tr class="invoice-item">
                            <td class="ta-l">{{ $detail['invoiceno'] }}</td>
                            <td class="ta-l">{{ $detail['date'] }}</td>
                            <td class="ta-r">{{ number_format($detail['finalprice'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @elseif($invoice->invoice_id)
                @php
                    $invoiceDetail = $invoice->invoice;
                @endphp
                <table style="margin-bottom: 5px;">
                    <thead>
                        <tr style="font-weight: bold; border-bottom: 1px solid #000;">
                            <td class="ta-l">Invoice No.</td>
                            <td class="ta-l">Date</td>
                            <td class="ta-r">Amount (RM)</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="invoice-item">
                            <td class="ta-l">{{ $invoiceDetail->invoiceno ?? '-' }}</td>
                            <td class="ta-l">{{ $invoiceDetail->date ? date_format(date_create($invoiceDetail->date), 'd/m/Y') : '-' }}</td>
                            <td class="ta-r">{{ number_format($invoice->amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <p class="ta-c">No invoice details available</p>
            @endif
        </div>

        <div class="divider-solid"></div>

        <!-- Payment Summary -->
        <table class="payment-section">
            <tr>
                <td class="ta-l" style="font-size: 14px;"><strong>Total Paid Amount :</strong></td>
                <td class="ta-r" style="font-size: 14px;"><strong>RM {{ number_format($invoice->amount, 2) }}</strong></td>
            </tr>
        </table>

        <div class="divider-solid"></div>

        <!-- Credit Information -->
        <table class="payment-section">
            <tr>
                <td class="ta-l">Previous Credit :</td>
                <td class="ta-r">RM {{ number_format($invoice->newcredit + $invoice->amount, 2) }}</td>
            </tr>
            <tr>
                <td class="ta-l">Payment Received :</td>
                <td class="ta-r">(RM {{ number_format($invoice->amount, 2) }})</td>
            </tr>
            <tr>
                <td class="ta-l" style="font-weight: bold;">Updated Credit :</td>
                <td class="ta-r" style="font-weight: bold;">RM {{ number_format($invoice->newcredit, 2) }}</td>
            </tr>
        </table>

        <!-- Remark Section (if any) -->
        @if(!empty($invoice->remark))
        <div class="divider"></div>
        <div class="address-label">REMARKS :</div>
        <div class="address-block">{{ $invoice->remark }}</div>
        @endif

        <div class="divider"></div>

    </div>
</body>
</html>