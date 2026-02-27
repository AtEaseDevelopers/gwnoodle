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
    <p class="tabletitle"><b>Summary</b></p>
    <table id="summary">
        <tr>
            <th>
                <label>Payment Date: </label>
            </th>
            <td>
                <label> {{$paymentdetail->datefrom.' ~ '.$paymentdetail->dateto}} </label>
            </td>
            <th>
                <label>Commission: </label>
            </th>
            <td class="number">
                <label> {{number_format($paymentdetail->do_amount,2)}} </label>
            </td>
        </tr>
        <tr>
            <th>
                <label>Driver Name: </label>
            </th>
            <td>
                <label> {{$paymentdetail->driver->name}} </label>
            </td>
            <th>
                <label>Deduct Amount: </label>
            </th>
            <td class="number">
                <label> {{number_format($paymentdetail->deduct_amount,2)}} </label>
            </td>
        </tr>
        <tr>
            <th>
                <label>Employee ID: </label>
            </th>
            <td>
                <label> {{$paymentdetail->driver->employeeid}} </label>
            </td>
            <th>
                <label>Amount after deduction: </label>
            </th>
            <td class="number">
                <label> {{number_format($paymentdetail->do_amount - $paymentdetail->deduct_amount,2)}} </label>
            </td>
        </tr>
        <tr>
            <th>
                <label>Group: </label>
            </th>
            <td>
                <label> {{$paymentdetail->driver->grouping}} </label>
            </td>
            <th>
                <label>Claim: </label>
            </th>
            <td class="number">
                <label> {{number_format($paymentdetail->claim_amount,2)}} </label>
            </td>
        </tr>
        <tr>
            <th>
                <label>IC: </label>
            </th>
            <td>
                <label> {{$paymentdetail->driver->ic}} </label>
            </td>
            <th>
                <label>Compound: </label>
            </th>
            <td class="number">
                <label> {{number_format($paymentdetail->comp_amount,2)}} </label>
            </td>
        </tr>
        <tr>
            <th>
                <label>Phone: </label>
            </th>
            <td>
                <label> {{$paymentdetail->driver->phone}} </label>
            </td>
            <th>
                <label>Advance: </label>
            </th>
            <td class="number">
                <label> {{number_format($paymentdetail->adv_amount,2)}} </label>
            </td>
        </tr>
        <tr>
            <th>
                <label>Bank Details 1: </label>
            </th>
            <td>
                <label> {{$paymentdetail->driver->bankdetails1}} </label>
            </td>
            <th>
                <label>Loan Pay: </label>
            </th>
            <td class="number">
                <label> {{number_format($paymentdetail->loanpay_amount,2)}} </label>
            </td>
        </tr>
        <tr>
            <th>
                <label>Bank Details 2: </label>
            </th>
            <td>
                <label> {{$paymentdetail->driver->bankdetails2}} </label>
            </td>
            <th>
                <label>Bonus: </label>
            </th>
            <td class="number">
                <label> {{number_format($paymentdetail->bonus_amount,2)}} </label>
            </td>
        </tr>
        <tr>
            <th></th>
            <td></td>
            <th>
                <label>Final Amount: </label>
            </th>
            <td class="number">
                <label> {{number_format($paymentdetail->final_amount,2)}} </label>
            </td>
        </tr>
      </table>
      @php
        //Drivers Commissions Report
        if(!function_exists('gettabledo')){
            function gettabledo($COLUMNS,$DATAS,$TABLETITLE){
                $result = '<div class="page_break"></div><p class="tabletitle"><b>' . $TABLETITLE . '</b></p><table>' . gettheaddo($COLUMNS) . gettbodydo($DATAS) . '</table>';
                return $result;
            }
        }
        if(!function_exists('gettheaddo')){
            function gettheaddo($COLUMNS){
                $result = '<thead><tr>';
                $count = 0;
                foreach ($COLUMNS as $COLUMN) {
                    switch ($count) {
                        case 0:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 1:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 2:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 3:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 4:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 5:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 9:
                            $result = $result . '<th>Loading Fee</th>';
                            break;
                        case 10:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 14:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 15:
                            $result = $result . '<th>Comm. Weight</th>';
                            break;
                        case 16:
                            $result = $result . '<th>Comm. Rate</th>';
                            break;
                        case 17:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 18:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 19:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        default:
                            break;
                    }
                    $count ++;
                }
                $result = $result . '</tr></thead>';
                return $result;
            }
        }
        if(!function_exists('gettbodydo')){
            function gettbodydo($DATAS){
                if($DATAS == null){
                    return '<tbody><tr></tr></tbody>';
                }
                $result = '<tbody>';
                $sum9 = 0;
                $sum10 = 0;
                $sum19 = 0;
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
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 9:
                                $sum9 = $sum9 + floatval(str_replace(',','',$COL));
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 10:
                                $sum10 = $sum10 + floatval(str_replace(',','',$COL));
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 14:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 15:
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 16:
                                $rowresult = $rowresult . '<td class="number">' . $COL . '</td>';
                                break;
                            case 17:
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 18:
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 19:
                                $sum19 = $sum19 + floatval(str_replace(',','',$COL));
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
                $result = $result . '</tbody><tfoot><tr><td></td><td></td><td></td><td></td><td></td><td></td><td class="number">' . number_format($sum9,2) . '</td><td class="number">' . number_format($sum10,2) . '</td><td></td><td></td><td></td><td></td><td></td><td class="number">' . number_format($sum19,2) . '</td></tr></tfoot>';
                return $result;
            }
        }
        $COLUMNS =  json_decode($result_do->data)[0]->COLUMNS;
        $DATAS =  json_decode($result_do->data)[0]->DATA;
        if($DATAS != null){
            echo gettabledo($COLUMNS,$DATAS,'Drivers Commissions Report');
        }
        
        //Drivers Claim Report
        if(!function_exists('gettableclaim')){
            function gettableclaim($COLUMNS,$DATAS,$TABLETITLE){
                $result = '<div class="page_break"></div><p class="tabletitle"><b>' . $TABLETITLE . '</b></p><table>' . gettheadclaim($COLUMNS) . gettbodyclaim($DATAS) . '</table>';
                return $result;
            }
        }
        if(!function_exists('gettheadclaim')){
            function gettheadclaim($COLUMNS){
                $result = '<thead><tr>';
                $count = 0;
                foreach ($COLUMNS as $COLUMN) {
                    switch ($count) {
                        case 2:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 3:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 4:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 7:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 8:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 9:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        default:
                            break;
                    }
                    $count ++;
                }
                $result = $result . '</tr></thead>';
                return $result;
            }
        }
        if(!function_exists('gettbodyclaim')){
            function gettbodyclaim($DATAS){
                if($DATAS == null){
                    return '<tbody><tr></tr></tbody>';
                }
                $result = '<tbody>';
                $sum9 = 0;
                foreach ($DATAS as $DATA) {
                    $rowresult = '<tr>';
                    $count = 0;
                    foreach ($DATA as $COL){
                        switch ($count) {
                            case 2:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 3:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 4:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 7:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 8:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 9:
                                $sum9 = $sum9 + floatval(str_replace(',','',$COL));
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
                $result = $result . '</tbody><tfoot><tr><td></td><td></td><td></td><td></td><td></td><td class="number">' . number_format($sum9,2) . '</td></tr></tfoot>';
                return $result;
            }
        }
        $COLUMNS =  json_decode($result_claim->data)[0]->COLUMNS;
        $DATAS =  json_decode($result_claim->data)[0]->DATA;
        if($DATAS != null){
            echo gettableclaim($COLUMNS,$DATAS,'Drivers Claim Report');
        }
        
        //Drivers Compound Report
        if(!function_exists('gettablecompound')){
            function gettablecompound($COLUMNS,$DATAS,$TABLETITLE){
                $result = '<div class="page_break"></div><p class="tabletitle"><b>' . $TABLETITLE . '</b></p><table>' . gettheadcompound($COLUMNS) . gettbodycompound($DATAS) . '</table>';
                return $result;
            }
        }
        if(!function_exists('gettheadcompound')){
            function gettheadcompound($COLUMNS){
                $result = '<thead><tr>';
                $count = 0;
                foreach ($COLUMNS as $COLUMN) {
                    switch ($count) {
                        case 2:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 3:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 4:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 7:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 8:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        default:
                            break;
                    }
                    $count ++;
                }
                $result = $result . '</tr></thead>';
                return $result;
            }
        }
        if(!function_exists('gettbodycompound')){
            function gettbodycompound($DATAS){
                if($DATAS == null){
                    return '<tbody><tr></tr></tbody>';
                }
                $result = '<tbody>';
                $sum7 = 0;
                $col8check = false;
                foreach ($DATAS as $DATA) {
                    $rowresult = '<tr>';
                    $count = 0;
                    foreach ($DATA as $COL){
                        switch ($count) {
                            case 2:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 3:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 4:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 7:
                                $sum7 = $sum7 + floatval(str_replace(',','',$COL));
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 8:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                $col8check = true;
                                break;
                            default:
                                break;
                        }
                        $count ++;
                    }
                    $rowresult = $rowresult . '</tr>';
                    $result = $result . $rowresult;
                }
                if($col8check){
                    $result = $result . '</tbody><tfoot><tr><td></td><td></td><td></td><td class="number">' . number_format($sum7,2) . '</td><td></td></tr></tfoot>';
                }else{
                    $result = $result . '</tbody><tfoot><tr><td></td><td></td><td></td><td class="number">' . number_format($sum7,2) . '</td></tr></tfoot>';
                }
                return $result;
            }
        }
        $COLUMNS =  json_decode($result_compound->data)[0]->COLUMNS;
        $DATAS =  json_decode($result_compound->data)[0]->DATA;
        if($DATAS != null){
            echo gettablecompound($COLUMNS,$DATAS,'Drivers Compound Report');
        }
        
        //Drivers Advance Report
        if(!function_exists('gettableadvance')){
            function gettableadvance($COLUMNS,$DATAS,$TABLETITLE){
                $result = '<div class="page_break"></div><p class="tabletitle"><b>' . $TABLETITLE . '</b></p><table>' . gettheadadvance($COLUMNS) . gettbodyadvance($DATAS) . '</table>';
                return $result;
            }
        }
        if(!function_exists('gettheadadvance')){
            function gettheadadvance($COLUMNS){
                $result = '<thead><tr>';
                $count = 0;
                foreach ($COLUMNS as $COLUMN) {
                    switch ($count) {
                        case 2:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 3:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 4:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 7:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        default:
                            break;
                    }
                    $count ++;
                }
                $result = $result . '</tr></thead>';
                return $result;
            }
        }
        if(!function_exists('gettbodyadvance')){
            function gettbodyadvance($DATAS){
                if($DATAS == null){
                    return '<tbody><tr></tr></tbody>';
                }
                $result = '<tbody>';
                $sum7 = 0;
                foreach ($DATAS as $DATA) {
                    $rowresult = '<tr>';
                    $count = 0;
                    foreach ($DATA as $COL){
                        switch ($count) {
                            case 2:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 3:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 4:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 7:
                                $sum7 = $sum7 + floatval(str_replace(',','',$COL));
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
                $result = $result . '</tbody><tfoot><tr><td></td><td></td><td></td><td class="number">' . number_format($sum7,2) . '</td></tr></tfoot>';
                return $result;
            }
        }
        $COLUMNS =  json_decode($result_advance->data)[0]->COLUMNS;
        $DATAS =  json_decode($result_advance->data)[0]->DATA;
        if($DATAS != null){
            echo gettableadvance($COLUMNS,$DATAS,'Drivers Advance Report');
        }
        
        //Drivers Loan Payment Report
        if(!function_exists('gettableloan')){
            function gettableloan($COLUMNS,$DATAS,$TABLETITLE){
                $result = '<div class="page_break"></div><p class="tabletitle"><b>' . $TABLETITLE . '</b></p><table>' . gettheadloan($COLUMNS) . gettbodyloan($DATAS) . '</table>';
                return $result;
            }
        }
        if(!function_exists('gettheadloan')){
            function gettheadloan($COLUMNS){
                $result = '<thead><tr>';
                $count = 0;
                foreach ($COLUMNS as $COLUMN) {
                    switch ($count) {
                        case 0:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 1:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 2:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 3:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        default:
                            break;
                    }
                    $count ++;
                }
                $result = $result . '</tr></thead>';
                return $result;
            }
        }
        if(!function_exists('gettbodyloan')){
            function gettbodyloan($DATAS){
                if($DATAS == null){
                    return '<tbody><tr></tr></tbody>';
                }
                $result = '<tbody>';
                $sum2 = 0;
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
                                $sum2 = $sum2 + floatval(str_replace(',','',$COL));
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 3:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            default:
                                break;
                        }
                        $count ++;
                    }
                    $rowresult = $rowresult . '</tr>';
                    $result = $result . $rowresult;
                }
                $result = $result . '</tbody><tfoot><tr><td></td><td></td><td class="number">' . number_format($sum2,2) . '</td><td></td></tr></tfoot>';
                return $result;
            }
        }
        $COLUMNS =  json_decode($result_loan->data)[0]->COLUMNS;
        $DATAS =  json_decode($result_loan->data)[0]->DATA;
        if($DATAS != null){
            echo gettableloan($COLUMNS,$DATAS,'Drivers Loan Payment Report');
        }
        
        //Drivers Bonus Report
        if(!function_exists('gettablebonus')){
            function gettablebonus($COLUMNS,$DATAS,$TABLETITLE){
                $result = '<div class="page_break"></div><p class="tabletitle"><b>' . $TABLETITLE . '</b></p><table>' . gettheadbonus($COLUMNS) . gettbodybonus($DATAS) . '</table>';
                return $result;
            }
        }
        if(!function_exists('gettheadbonus')){
            function gettheadbonus($COLUMNS){
                $result = '<thead><tr>';
                $count = 0;
                foreach ($COLUMNS as $COLUMN) {
                    switch ($count) {
                        case 0:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 1:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 2:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 3:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 4:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 7:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 8:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        // case 9:
                        //     $result = $result . '<th>' . $COLUMN->title . '</th>';
                        //     break;
                        default:
                            break;
                    }
                    $count ++;
                }
                $result = $result . '</tr></thead>';
                return $result;
            }
        }
        if(!function_exists('gettbodybonus')){
            function gettbodybonus($DATAS){
                if($DATAS == null){
                    return '<tbody><tr></tr></tbody>';
                }
                $result = '<tbody>';
                $sum7 = 0;
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
                                // $rowresult = $rowresult . '<td class="number">' . number_format($COL,2,'.','') . '</td>';
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 4:
                                // $rowresult = $rowresult . '<td class="number">' . number_format($COL,2,'.','') . '</td>';
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 7:
                                $sum7 = $sum7 + floatval(str_replace(',','',$COL));
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 8:
                                $rowresult = $rowresult . '<td class="number">' . $COL . '</td>';
                                break;
                            // case 9:
                            //     $dolist = '';
                            //     foreach (json_decode($COL) as $do) {
                            //         $dolist = $dolist . $do . ',<br>';
                            //     }
                            //     $rowresult = $rowresult . '<td class="number">' . $dolist . '</td>';
                            //     break;
                            default:
                                break;
                        }
                        $count ++;
                    }
                    $rowresult = $rowresult . '</tr>';
                    $result = $result . $rowresult;
                }
                // $result = $result . '</tbody><tfoot><tr><td></td><td></td><td></td><td></td><td></td><td class="number">' . number_format($sum7,2) . '</td><td></td><td></td></tr></tfoot>';
                $result = $result . '</tbody><tfoot><tr><td></td><td></td><td></td><td></td><td></td><td class="number">' . number_format($sum7,2) . '</td><td></td></tr></tfoot>';
                return $result;
            }
        }
        $COLUMNS =  json_decode($result_bonus->data)[0]->COLUMNS;
        $DATAS =  json_decode($result_bonus->data)[0]->DATA;
        if($DATAS != null){
            echo gettablebonus($COLUMNS,$DATAS,'Drivers Bonus Report');
        }
        
        //Previous Drivers Commissions Report
        if(!function_exists('gettablepdo')){
            function gettablepdo($COLUMNS,$DATAS,$TABLETITLE){
                $result = '<div class="page_break"></div><p class="tabletitle"><b>' . $TABLETITLE . '</b></p><table>' . gettheadpdo($COLUMNS) . gettbodypdo($DATAS) . '</table>';
                return $result;
            }
        }
        if(!function_exists('gettheadpdo')){
            function gettheadpdo($COLUMNS){
                $result = '<thead><tr>';
                $count = 0;
                foreach ($COLUMNS as $COLUMN) {
                    switch ($count) {
                        case 0:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 1:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 2:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 3:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 4:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 5:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 9:
                            $result = $result . '<th>Loading Fee</th>';
                            break;
                        case 10:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 14:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 15:
                            $result = $result . '<th>Comm. Weight</th>';
                            break;
                        case 16:
                            $result = $result . '<th>Comm. Rate</th>';
                            break;
                        case 17:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 18:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        case 19:
                            $result = $result . '<th>' . $COLUMN->title . '</th>';
                            break;
                        default:
                            break;
                    }
                    $count ++;
                }
                $result = $result . '</tr></thead>';
                return $result;
            }
        }
        if(!function_exists('gettbodypdo')){
            function gettbodypdo($DATAS){
                if($DATAS == null){
                    return '<tbody><tr></tr></tbody>';
                }
                $result = '<tbody>';
                $sum9 = 0;
                $sum10 = 0;
                $sum19 = 0;
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
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 9:
                                $sum9 = $sum9 + floatval(str_replace(',','',$COL));
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 10:
                                $sum10 = $sum10 + floatval(str_replace(',','',$COL));
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 14:
                                $rowresult = $rowresult . '<td>' . $COL . '</td>';
                                break;
                            case 15:
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 16:
                                $rowresult = $rowresult . '<td class="number">' . $COL . '</td>';
                                break;
                            case 17:
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 18:
                                $rowresult = $rowresult . '<td class="number">' . number_format(floatval(str_replace(',','',$COL)),2) . '</td>';
                                break;
                            case 19:
                                $sum19 = $sum19 + floatval(str_replace(',','',$COL));
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
                $result = $result . '</tbody><tfoot><tr><td></td><td></td><td></td><td></td><td></td><td></td><td class="number">' . number_format($sum9,2) . '</td><td class="number">' . number_format($sum10,2) . '</td><td></td><td></td><td></td><td></td><td></td><td class="number">' . number_format($sum19,2) . '</td></tr></tfoot>';
                return $result;
            }
        }
        $COLUMNS =  json_decode($result_pdo->data)[0]->COLUMNS;
        $DATAS =  json_decode($result_pdo->data)[0]->DATA;
        if($DATAS != null){
            echo gettablepdo($COLUMNS,$DATAS,'Previous Drivers Commissions Report');
        }

      @endphp
      {{-- <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script('
                $text = sprintf(_("Page %d/%d"),  $PAGE_NUM, $PAGE_COUNT);
                // Uncomment the following line if you use a Laravel-based i18n
                //$text = __("Page :pageNum/:pageCount", ["pageNum" => $PAGE_NUM, "pageCount" => $PAGE_COUNT]);
                $font = null;
                $size = 9;
                $color = array(0,0,0);
                $word_space = 0.0;  //  default
                $char_space = 0.0;  //  default
                $angle = 0.0;   //  default
    
                // Compute text width to center correctly
                $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
    
                $x = ($pdf->get_width() - $textWidth) / 2;
                $y = $pdf->get_height() - 35;
    
                $pdf->text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
            '); // End of page_script
        }
    </script> --}}
</body>

</html>