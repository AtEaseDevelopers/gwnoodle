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
            font-size: 14px;
            padding-top:6px;
        }

        .login-image{
            background-image: url('{{config('app.url')}}/logo.png');
            width: auto;
            height: 75px;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            margin-bottom: 1rem;
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
            color:#CB8553;
            font-size:16px;
        }
        #note
        {
            padding-top:10px;
            line-height:2em;
        }
        .qr-code-container{
            text-align: center;
            margin: 10px 0;
        }
        .qr-code-container img{
            max-width: 120px;
            max-height: 120px;
        }
        .validation-info{
            font-size: 11px;
            margin-top: 5px;
            line-height: 1.3;
        }
    </style>
</head>
<body>
    <table class="invoice">
        <tr>
            <td>
                <p class="company">{{ $invoice['customer']['groupcompany']->name ?? env('INVOICE_NAME') }}</p>
            </td>
        </tr>
      <tr>
            <td>
                <p class="address">{{ $invoice['customer']['groupcompany']->ssm ?? env('INVOICE_SSM') }}</p>
            </td>
        </tr> 
        <tr>
            <td>
                <p class="address">{{ $invoice['customer']['groupcompany']->address1 ?? env('INVOICE_ADDRESS1') }}</p>
            </td>
        </tr>
        <tr>
            <td>
                <p class="address">{{ $invoice['customer']['groupcompany']->address2 ?? env('INVOICE_ADDRESS2') }}</p>
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
                        <td>
                            <p>R.No</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $invoice['invoiceno'] ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p>Invoice Date</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ date_format(date_create($invoice['date']),'Y-m-d H:i:s') ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            <p>Address</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $invoice['customer']['address'] ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            <p>Transaction by</p>
                        </td>
                        <td>
                            <p class="ta-r">{{ $invoice['driver']['name'] ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            <p>Customer</p>
                        </td>
                        <td>
                            <p class="ta-r" style="">{{ $invoice['customer']['company'] ?? '' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            <p>Payment method</p>
                        </td>
                        <td>
                            <p class="ta-r" style="">
                                
                            @if($invoice['paymentterm'] == 1)
                                {{ 'Cash' }}
                            @elseif($invoice['paymentterm'] == 2)   
                                {{ 'Bankin' }}
                            @elseif($invoice['paymentterm'] == 3)   
                                {{ 'Credit Note' }}
                           @elseif($invoice['paymentterm'] == 4)   
                            {{ 'Touch & Go' }}
                            @endif
                            </p>
                        </td>
                    </tr>
                    @if($invoice['remark'])
                     <tr>
                        <td valign="top">
                            <p>Remark</p>
                        </td>
                        <td>
                            <p class="ta-r" style="">{{ $invoice['remark'] ?? '' }}</p>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td valign="top">
                            <p>E-Invoice No</p>
                        </td>
                        <td>
                            <p class="ta-r" style="">{{ $einvoice->sku }}</p>
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
                        <th style="width:15%;">
                            <p class="" align="center">Qty</p>
                        </th>
                        <th style="width:30%;">
                            <p class="ta-l">Product</p>
                        </th>
                        <th>
                            <p class="ta-r">Price</p>
                        </th>
                        <th>
                            <p class="ta-r">Amt(RM)</p>
                        </th>
                    </tr>
                    @foreach ($invoice['invoicedetail'] as $invoicedetail)
                        @php
                            $totalamount = ($totalamount ?? 0) + $invoicedetail['totalprice'];
                        @endphp
                        <tr>
                             <td>
                                <p  align="center">{{ $invoicedetail['quantity'] }}</p>
                            </td>
                            <td>
                                <p >{{ $invoicedetail['product']['name'] }}</p>
                            </td>
                            <td>
                                <p class="ta-r" style="">{{ number_format($invoicedetail['price'],2) }}</p>
                            </td>
                           
                            <td>
                                <p class="ta-r" style="">{{ number_format($invoicedetail['totalprice'],2) }}</p>
                            </td>
                        </tr>
                    @endforeach
                        <tr>
                            <td height="5" colspan="4"></td>
                        </tr>
                         <tr>
                             <td colspan="3">
                              Subtotal ({{  $invoice['invoicedetail']->count() }})
                            </td>
                            <td>
                                <p class="ta-l" style="">RM {{ number_format($totalamount,2) }}</p>
                            </td>
                        </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <hr>
                <table id="total">
                    <tr>
                        <th>
                            <p class="ta-l">Total</p>
                        </th>
                        <th>
                            <p class="ta-r" >RM {{ number_format($totalamount,2) }}</p>
                        </th>
                    </tr>
                </table>
                <p class="paidsummary">Paid Summary</p>
                <table id="footer">
                     <tr>
                        <td>
                            <p class="ta-l" style="">Credit</p>
                        </td>
                        <td>
                            <p class="ta-r" style="">RM {{ number_format($invoice["offset_amount"],2) }}</p>
                        </td>
                    </tr>
                     <tr>
                        <td>
                            <p class="ta-l" style="font-size:22px;">Remaining</p>
                        </td>
                        <td>
                            <p class="ta-r" style="font-size:22px">RM {{ number_format($totalamount - $invoice["offset_amount"],2) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p class="ta-l" style="">Paid Amount</p>
                        </td>
                        <td>
                            <p class="ta-r" style="">RM {{ number_format($invoice["paid_amount"],2) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p class="ta-l" style="">Updated Credit</p>
                        </td>
                        <td>
                            <p class="ta-r" style="">RM {{ number_format($invoice->newcredit ?? 0,2) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" align="center" id="note">
                            <p style="">Thank You!</p>
                            <p style="">Please come again!</p>
                            <p style="">Goods Sold Are Not Refundable!</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @if($qrCodeData && isset($qrCodeData['image']))
        <tr>
            <td colspan="2" align="center" style="padding-top: 20px;">
                <div class="qr-code-container">
                    <img src="data:{{ $qrCodeData['content_type'] ?? 'image/png' }};base64,{{ $qrCodeData['image'] }}" alt="QR Code">
                </div>
                @if($apiDetails)
                    @if(isset($apiDetails['submissionUid']))
                        <p class="validation-info"><strong>Unique Identifier No:</strong><br>{{ $apiDetails['submissionUid'] }}</p>
                    @endif
                    @if(isset($apiDetails['dateTimeValidated']))
                        <p class="validation-info"><strong>Date and Time of Validation:</strong><br>{{ $apiDetails['dateTimeValidated'] }}</p>
                    @endif
                @endif
            </td>
        </tr>
        @endif
    </table>
</body>

</html>
