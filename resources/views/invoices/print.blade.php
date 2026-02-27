<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{config('app.name')}}</title>
    <style>
        @page {
            margin-bottom:30px;
            margin-top:30px;
            margin-left:30px;
            margin-right:30px;
        }
        body{
            font-size: 14px;
            margin: 0%;
            font-family: Arial, Helvetica, sans-serif;
        }
        table{
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table th, table td{
            /* border: 1px solid black; */
            font-size: 12px;
        }

        .login-image{
            background-image: url('{{config('app.url')}}/logo.png');
            width: auto;
            height: 55px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            margin-bottom: 0.5rem;
        }
        .company{
            font-weight: bold;
            text-align: center;
        }
        .address{
            text-align: center;
        }
        p{
            margin: 0%;
        }
        .ta-r{
            text-align: right;
        }
        .ta-l{
            text-align: left;
        }
        .paidsummary{
            text-align: center;
            font-weight: bold;
            color: #394068;
        }
    </style>
</head>
<body>
    <table class="invoice">
        <tr>
            <td>
                <div class="login-image"></div>
            </td>
        </tr>
        <tr>
            <td>
                <p class="company">{{ $invoice['customer']['groupcompany']->name ?? config('invoice.name') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ $invoice['customer']['groupcompany']->ssm ?? config('invoice.ssm') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ $invoice['customer']['groupcompany']->address1 ?? config('invoice.address1') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ $invoice['customer']['groupcompany']->address2 ?? config('invoice.address2') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ $invoice['customer']['groupcompany']->address3 ?? env('INVOICE_ADDRESS3') }}</p>
            </td>
        </tr>
       
        <tr>
            <td>
                <br>
                <table id="header">
                    <tr>
                        <td width="35%">
                            <p>Invoice</p>
                        </td>
                        <td width="65%">
                            <p class="ta-r">{{ $invoice['invoiceno'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Invoice Date</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ date_format(date_create($invoice['date']),'d-m-Y H:i:s') ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Payment Method</p>
                        </td>
                        <td>
                            <p class="ta-r">
                            @if($invoice['paymentterm']==1)
                                {{ 'Cash' }}
                            @elseif($invoice['paymentterm']==2)
                                {{ 'Credit'}}
                            @elseif($invoice['paymentterm']==3)
                                {{ 'Online BankIn'}}
                            @elseif($invoice['paymentterm']==4)
                                {{ 'E-wallet'}}
                            @elseif($invoice['paymentterm']==5)
                                {{ 'Cheque'}}
                            @endif
                            </p>
                        </td>
                    </tr>
                    
                    @if($invoice['paymentterm']==5)
                    <tr>
                        <td>
                            <p>Cheque No</p>
                        </td>
                        <td>
                            <p class="ta-r">
                            {{ $invoice['chequeno'] }}
                            </p>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td>
                            <p>Address</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $invoice['customer']['address'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Driver</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $invoice['driver']['name'] ?? '-' }}</p>
                        </td>
                    </tr>
                    
                    <tr><td height="15">&nbsp;</td></tr>
                    <tr>
                        <td>
                            <p style="font-size:16px; font-weight:bold;">Customer</p>
                        </td>
                        <td>
                            <p class="ta-r" style="font-size:16px; font-weight:bold;">{{ $invoice['customer']['company'] ?? '-' }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <br>
                <table id="detail">
                    <tr>
                        <th>
                            <p class="ta-l">Product</p>
                        </th>
                        <th>
                            <p class="ta-r">Price <br>(RM)</p>
                        </th>
                        <th>
                            <p class="ta-r">Qty</p>
                        </th>
                        <th>
                            <p class="ta-r">Subtotal</p>
                        </th>
                    </tr>
                    @php
                            $totalamount = 0;
                    @endphp
                    @foreach ($invoice['invoicedetail'] as $invoicedetail)
                        @php
                            $totalamount = ($totalamount ?? 0) + $invoicedetail['totalprice'];
                        @endphp
                        <tr>
                            <td>
                                <p style="font-size:16px;">{{ $invoicedetail['product']['name'] }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">{{ number_format($invoicedetail['price'],2) }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">{{ $invoicedetail['quantity'] }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">{{ number_format($invoicedetail['totalprice'],2) }}</p>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <br>
                <table id="total">
                    <tr>
                        <th>
                            <p class="ta-l" style="font-size:18px;">Total</p>
                        </th>
                        <th>
                            <p class="ta-r" style="font-size:18px;">RM{{ number_format($totalamount,2) }}</p>
                        </td>
                    </tr>
                </table>
                <p class="paidsummary">Paid Summary</p>
                <table id="footer">
                    <tr>
                        <th>
                            <p class="ta-l" style="font-size:18px;">Paid Amount</p>
                        </th>
                        <td>
                            <p class="ta-r" style="font-size:18px;">RM{{ number_format($totalamount,2) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <p class="ta-l" style="font-size:18px;">Updated Credit</p>
                        </th>
                        <td>
                            <p class="ta-r" style="font-size:18px;">RM{{ number_format($invoice->newcredit,2) }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
