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
                <p class="address">012-9147018/03-33413598</p>
            </td>
        </tr>
        <tr>
            <td>
                <br>
                <table id="header">
                    <tr>
                         <td width="35%">
                            <p>Payment No.</p>
                        </td>
                       <td width="65%">
                            <p class="ta-r">{{ sprintf('PR%05d',$invoice->id) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Payment Date</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ date_format(date_create($invoice['updated_at']),'Y-m-d H:i:s') ?? '-' }}</p>
                        </td>
                    </tr>
                   <tr>
                        <td>
                            <p>Payment Method</p>
                        </td>
                        <td>
                            <p class="ta-r">
                            @if($invoice['type']==1)
                                {{ 'Cash' }}
                            @elseif($invoice['type']==2)
                                {{ 'Credit'}}
                            @elseif($invoice['type']==3)
                                {{ 'Online BankIn'}}
                            @elseif($invoice['type']==4)
                                {{ 'E-wallet'}}
                            @elseif($invoice['type']==5)
                                {{ 'Cheque'}}
                            @endif
                            </p>
                        </td>
                    </tr>
                    
                    @if($invoice['type']==5)
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
                            <p>Customer</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $invoice['customer']['company'] ?? '-' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Approved By</p>
                        </td>
                        <td>
                            <p class="ta-r" style='font-size:12px;'>{{ $invoice->approve_by }}</p>
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
                    
                        <tr>
                            <td>
                                <p style="font-size:16px;">CREDIT PAYMENT</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">{{ number_format($invoice->amount,2) }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">1</p>
                            </td>
                            <td>
                                <p class="ta-r" style="font-size:16px;">{{ number_format($invoice->amount,2) }}</p>
                            </td>
                        </tr>
                    
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
                            <p class="ta-r" style="font-size:18px;">RM{{ number_format($invoice->amount,2) }}</p>
                        </td>
                    </tr>
                </table>
                <p class="paidsummary">Paid Summary</p>
                <table id="footer">
                  
                    <tr>
                        <th>
                            <p class="ta-l" style="font-size:22px;">Paid Amount</p>
                        </th>
                        <td>
                            <p class="ta-r" style="font-size:22px;">RM{{ number_format($invoice->amount,2) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <p class="ta-l" style="font-size:22px;">Updated Credit</p>
                        </th>
                        <td>
                            <p class="ta-r" style="font-size:22px;">RM{{ number_format($invoice->newcredit,2) }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
    </table>
</body>

</html>
