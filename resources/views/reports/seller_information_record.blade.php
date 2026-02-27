<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{config('app.name')}}</title>
    <style>
        @page {
            margin-bottom:10px;
            margin-top:10px;
            margin-left:10px;
            margin-right:10px;
        }
        body{
            font-size: 18px;
            margin: 0%;
        }
        table{
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table th, table td{
            border: 1px solid black;
            font-size: 15px;
        }
        .tabletitle{
            margin: 0%;
            width: 100%;
            text-align: center;
        }

        .no-border td{
            border: none;
        }
        .border-solid {
            border: solid;
        }
        .ta-r {
            text-align: right;
        }
        .ta-l {
            text-align: left ;
        }
        .ta-c {
            text-align: center;
        }
        .mt-5px {
            margin-top: 5px;
        }
        .mb-5px {
            margin-bottom: 5px;
        }
        .ms-5 {
            margin-left: 5%;
            margin-right: 5%;
            width: 90%;
        }
        .border-bottom-5 {
            border-bottom: solid 1px;
        }
        td {
            padding-left: 5px;
            padding-right: 5px;
        }
        .content {
            border-spacing: 140px;
        }
    </style>
</head>
<body>
    <div style="page-break-inside: avoid;">
    <table>
        <thead>
            <tr class="no-border">
                <td colspan="3">
                    <h3 class="tabletitle"><b>{{ env('INVOICE_NAME') }}</b></h3>
                </td>
            </tr>
            <tr class="no-border">
                <td colspan="3">
                    <h3 class="tabletitle"><b>SELLER INFORMATION RECORD (PROD-15)</b></h3>
                </td>
            </tr>
            <tr class="no-border">
                <td colspan="3">
                    <h3 class="tabletitle"><b>Invoice Listing</b></h3>
                </td>
            </tr>
            <tr class="no-border">
                <td class="ta-l">Agent: {{ $params['p_agents'] }}</td>
                <td class="ta-c">As at {{ $params['p_datefrom'] }} - {{ $params['p_dateto'] }}</td>
                <td class="ta-r"> {{ date('Y-m-d H:i:s') }} </td>
            </tr>
            <tr class="no-border">
                <td class="ta-l">Customer: {{ $params['p_customers'] }}</td>
                <td class="ta-c"></td>
                <td class="ta-r"></td>
            </tr>
            <tr class="no-border">
                <td colspan="3">
                    <table class="no-border">
                        <tr class="border-solid">
                            <td class="ta-l"><b>Doc. No</b></td>
                            <td class="ta-l"><b>Doc. Date</b></td>
                            <td class="ta-l"><b>Code</b></td>
                            <td class="ta-l"><b>Name</b></td>
                            <td class="ta-r"><b>Amount (RM)</b></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </thead>
        <tbody>
            <tr class="no-border">
                <td colspan="3">
                    <table class="no-border">
                        @foreach ($invoices as $invoice)
                            {{-- first for each --}}
                            <tr class="content">
                                <td class="ta-l"><b>{{ $invoice['invoiceno'] }}</b></td>
                                <td class="ta-l"><b>{{ $invoice['date'] }}</b></td>
                                <td class="ta-l"><b>{{ $invoice['customer']['code'] }}</b></td>
                                <td class="ta-l"><b>{{ $invoice['customer']['company'] }}</b></td>
                                <td class="ta-r"><b>{{ number_format($invoice['invoicedetail_sum_totalprice'],2) }}</b></td>
                            </tr>
                            <tr>
                                <td colspan="5">
                                    <table class="no-border ms-5 mt-5px mb-5px">
                                        <tr class="border-bottom-5">
                                            <td class="ta-l">Seq</td>
                                            <td class="ta-l">Description</td>
                                            <td class="ta-r">Quantity</td>
                                            <td class="ta-l">UOM</td>
                                            <td class="ta-r">Unit Price</td>
                                            <td class="ta-r">Amount (RM)</td>
                                        </tr>
                                        @foreach($invoice['invoicedetail'] as $i=>$id)
                                            {{-- second for each --}}
                                            <tr class="border-bottom-5">
                                                <td class="ta-l">{{ sprintf('%03d',$i) }}</td>
                                                <td class="ta-l">{{ $id['product']['name'] }}</td>
                                                <td class="ta-r">{{ number_format($id['quantity'],2) }}</td>
                                                <td class="ta-l">BAG</td>
                                                <td class="ta-r">{{ number_format($id['price'],4) }}</td>
                                                <td class="ta-r">{{ number_format($id['totalprice'],2) }}</td>
                                            </tr>
                                            {{-- second for each --}}
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                            {{-- first for each --}}
                        @endforeach
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    <script type="text/php">
        if ( isset($pdf) ) {
            $pdf->page_text(530, 76, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0,0,0));
        }
    </script>
</body>
</html>
