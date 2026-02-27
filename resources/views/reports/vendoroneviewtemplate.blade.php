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
        #summary th{
            text-align: left;
        }
        #summary th, #summary td{
            width: 25%;
            font-size: 18px !important; 
        }
        .number{
            text-align: right;
        }
        .number label{
            padding-right: 100px;
        }
        .tabletitle{
            margin: 0%;
            width: 100%;
        }
        .page_break{ 
            page-break-before: always; 
        }
    </style>
</head>
<body>
    <p class="tabletitle"><b>Vendor Summary</b></p>
    <table id="summary">
        <tr>
            <th>
                <label>Billing Date: </label>
            </th>
            <td>
                <label> {{$datefrom.' ~ '.$dateto}} </label>
            </td>
        </tr>
        <tr>
            <th>
                <label>Vendor Name: </label>
            </th>
            <td>
                <label> {{$vendor->name}} </label>
            </td>
        </tr>
        <tr>
            <th>
                <label>Vendor Code: </label>
            </th>
            <td>
                <label> {{$vendor->code}} </label>
            </td>
        </tr>
        <tr>
            <th>
                <label>Phone: </label>
            </th>
            <td>
                <label> {{$vendor->phone}} </label>
            </td>
        </tr>
        <tr>
            <th>
                <label>Grand Total Sales (RM): </label>
            </th>
            <td>
                <label id="grandtotalsales">
                    @php
                        $grandtotalsales = 0;
                        foreach ($vendorbillings as $vendorbilling) {
                            $grandtotalsales = $grandtotalsales + $vendorbilling->totaldosales;
                        }
                        echo number_format($grandtotalsales, 2);
                    @endphp 
                </label>
            </td>
        </tr>
    </table>
    @php
        if(!function_exists('gettable')){
            function gettable($COLUMNS,$DATAS,$TABLETITLE,$totalshipweight,$totaldosales){
                $result = '<div class="page_break"></div><p class="tabletitle"><b>' . $TABLETITLE . '</b></p><table id="summary"> <tr> <th> <label>Total Weight: </label> </th> <td> <label>'.$totalshipweight.'</label> </td></tr><tr> <th> <label>Total Sales: </label> </th> <td> <label>'.number_format($totaldosales, 2).'</label> </td></tr></table><table>' . getthead($COLUMNS) . gettbody($DATAS) . '</table>';
                return $result;
            }
        }
        if(!function_exists('getthead')){
            function getthead($COLUMNS){
                $result = '<thead><tr>';
                $count = 0;
                foreach ($COLUMNS as $COLUMN) {
                    $result = $result . '<th>' . $COLUMN->title . '</th>';
                    $count ++;
                }
                $result = $result . '</tr></thead>';
                return $result;
            }
        }
        if(!function_exists('gettbody')){
            function gettbody($DATAS){
                if($DATAS == null){
                    return '<tbody><tr></tr></tbody>';
                }
                $result = '<tbody>';
                foreach ($DATAS as $DATA) {
                    $rowresult = '<tr>';
                    $count = 0;
                    foreach ($DATA as $COL){
                        switch ($count) {
                            case 0:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 1:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 2:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 3:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 4:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 5:
                                $rowresult = $rowresult . '<td class="number">' . $COL . '</td>';
                                break;
                            case 6:
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 7:
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            default:
                                break;
                        }
                        $count ++;
                    }
                    $rowresult = $rowresult . '</tr>';
                    $result = $result . $rowresult;
                }
                // $result = $result . '</tbody><tfoot><tr><td></td><td></td><td></td><td></td><td></td><td></td><td class="number">' . number_format($sum9,2) . '</td><td class="number">' . number_format($sum10,2) . '</td><td></td><td></td><td></td><td></td><td></td><td class="number">' . number_format($sum19,2) . '</td></tr></tfoot>';
                return $result;
            }
        }
        foreach ($vendorbillings as $vendorbilling) {
            $COLUMNS = json_decode($vendorbilling->table)[0]->COLUMNS;
            $DATAS = json_decode($vendorbilling->table)[0]->DATA;
            $TABLETITLE = $vendorbilling->item_name . ' from ' . $vendorbilling->source_Name . ' to ' . $vendorbilling->destination_name;
            $totalshipweight = $vendorbilling->totalshipweight;
            $totaldosales = $vendorbilling->totaldosales;
            echo gettable($COLUMNS,$DATAS,$TABLETITLE,$totalshipweight,$totaldosales);
        }
    @endphp
</body>

</html>