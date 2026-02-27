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
                <td colspan="2" class="ta-r">
                    <img width="120px" src="{{ env('APP_URL') }}/logo.png">
                </td>
                <td colspan="7">
                    <table>
                        <tr>
                            <td style="width:255px;">
                                <h2 class="tabletitle" style="width:350px;"><b>{{ env('INVOICE_NAME') }}</b></h2>
                            </td>
                            <td style="font-size:10px;vertical-align:bottom;text-align:left;">
                                <p class="tabletitle"></p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <p class="tabletitle">{{ env('INVOICE_SSM') }} {{ env('INVOICE_ADDRESS1') }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <p class="tabletitle">{{ env('INVOICE_ADDRESS2') }}</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <p class="tabletitle"><!--Phone: 03-80630334 / 019-3273568 email: captainicesdnbhd@gmail.com--></p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr style="height:5px;border-bottom:solid 1px;">
            </tr>
            <tr class="no-border mt-5px">
                <td colspan="5">
                    <table>
                        <tr>
                            <td><p class="tabletitle" style="font-size:10px;">Customer</p></td>
                        </tr>
                        <tr>
                            <td>{{ $params['p_customers']['company'] }}</td>
                        </tr>
                        <tr>
                            <td>{{ $params['p_customers']['address'] }}</td>
                        </tr>
                        <tr>
                            <td>Tel: {{ $params['p_customers']['phone'] }}</td>
                        </tr>
                    </table>
                </td>
                <td colspan="4">
                    <table style="border:solid 1px">
                        <tr class="ta-c" style="border-bottom:solid 1px;">
                            <td colspan="2"><h2 class="tabletitle"><b>Statement of Account</b></h2></td>
                        </tr>
                        <tr>
                            <td class="ta-l">Total Debit ({{ $params['total_debit'] }})</td>
                            <td class="ta-r">{{ number_format($params['sum_debit'],2) }}</td>
                        </tr>
                        <tr style="border-bottom:solid 1px;">
                            <td class="ta-l">Total Credit ({{ $params['total_credit'] }})</td>
                            <td class="ta-r">{{ number_format($params['sum_credit'],2) }}</td>
                        </tr>
                        <tr>
                            <td class="ta-l">Closing Balance</td>
                            <td class="ta-r">{{ number_format($params['sum_debit'] - $params['sum_credit'],2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr style="height:5px;">
            </tr>
            <tr class="no-border" style="border-top:solid 1px;">
                <td colspan="2">Customer Account</td>
                <td colspan="3">Customer Name</td>
                <td colspan="2">Start Date</td>
                <td colspan="2">End Date</td>
            </tr>
            <tr class="no-border" style="border-bottom:solid 1px;">
                <td colspan="2"><b>{{ $params['p_customers']['code'] }}</b></td>
                <td colspan="3"><b>{{ $params['p_customers']['company'] }}</b></td>
                <td colspan="2"><b>{{ $params['p_datefrom'] }}</b></td>
                <td colspan="2"><b>{{ $params['p_dateto'] }}</b></td>
            </tr>
            <tr class="no-border">
                <td colspan="2">Date</td>
                <td>Reference</td>
                <td colspan="3">Transaction Description</td>
                <td>Debit</td>
                <td>Credit</td>
                <td>Balance</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $d)
                @php
                    $balance = ($balance ?? 0) + $d['debit'] - $d['credit'];
                @endphp
                <tr class="no-border">
                    <td colspan="2">{{ $d['date'] }}</td>
                    <td>{{ $d['reference'] }}</td>
                    <td colspan="3">{{ $d['descr'] }}</td>
                    <td class="ta-r">{{ number_format($d['debit'],2) }}</td>
                    <td class="ta-r">{{ number_format($d['credit'],2) }}</td>
                    <td class="ta-r">{{ number_format($balance,2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <script type="text/php">
        if ( isset($pdf) ) {
            $pdf->page_text(530, 56, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 10, array(0,0,0));
        }
    </script>
</body>
</html>
