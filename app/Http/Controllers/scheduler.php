<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Code;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Kelindan;

class scheduler extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
    }

    public function updateDoRate()
    {
        Log::error('Scheduler Job Started: updateDoRate');
        $currentTimeStamp = date("Y-m-d H:i:s");
        $defaultemail = Code::where('code','emailreceiver')->select('value')->get()->toarray();
        $defaultemails = [];
        $result = DB::select('call spUpdateDeliveryOrderRate();')[0]->count;
        Log::error('updateDoRate: '.$result.' records');
        if($result > 0){
            foreach($defaultemail as $de){
                $mailstatus = app('App\Http\Controllers\sender')->email('All',$de['value'],'','Upadate D/O rate Job - ' . env('APP_URL'),'<b>Update Delivery Order Billing Rate & Commission Rate Job had been done</b><br>- Job Run Time: '.$currentTimeStamp.'<br>- '.$result.' delivery order(s) had been updated');
            }
            Log::error('updateDoRate email status: '.response()->json($mailstatus));
            return response()->json($mailstatus);
        }else{
            Log::error('updateDoRate email status: No DO can update....');
            return 'No DO can update....';
        }
    }

    public function archivedDeliveryOrder()
    {
        Log::error('Scheduler Job Started: archivedDeliveryOrder');
        $currentTimeStamp = date("Y-m-d H:i:s");
        $defaultemail = Code::where('code','emailreceiver')->select('value')->get()->toarray();
        $defaultemails = [];
        $result = DB::select('call spArcDeliveryOrder();')[0]->count;
        Log::error('archivedDeliveryOrder: '.$result.' records');
        foreach($defaultemail as $de){
            $mailstatus = app('App\Http\Controllers\sender')->email('All',$de['value'],'','Archived D/O Job - ' . env('APP_URL'),'<b>Archived Delivery Order Job had been done</b><br>- Job Run Time: '.$currentTimeStamp.'<br>- '.$result.' delivery order(s) had been archived');
        }
        Log::error('archivedDeliveryOrder email status: '.response()->json($mailstatus));
        return response()->json($mailstatus);
    }

    public function checkLorryService()
    {
        Log::error('Scheduler Job Started: checkLorryService');
        $currentTimeStamp = date("Y-m-d H:i:s");
        $currentDate = date("Y-m-d");
        $defaultemail = Code::where('code','emailreceiverlorry')->select('value')->get()->toarray();

        $result = DB::select('call spGetLorryService();');
        $body = '';
        foreach($result as $r){
            $body = $body . '<b>Lorry '.$r->lorryno.' '.$r->type.' Service will be expired in '.$r->value.' day(s)</b><br>-Lorry Number: '.$r->lorryno.'<br>-Service Type: '.$r->type.'<br>-Due Date: '.$r->nextdate.'<br><br>';
        }
        if($body != ''){
            foreach($defaultemail as $de){
                $mailstatus = app('App\Http\Controllers\sender')->email('All',$de['value'],'','Lorry Service Notification: '.$currentDate . ' - ' . env('APP_URL'),$body);
            }
            Log::error('checkLorryService email status: '.response()->json($mailstatus));
            return response()->json($mailstatus);
        }else{
            Log::error('checkLorryService email status: No lorry need to service....');
            return 'No lorry need to service....';
        }
    }

    public function checkKelindanPermit()
    {
        Log::error('Scheduler Job Started: checkKelindanPermit');
        $currentTimeStamp = date("Y-m-d H:i:s");
        $currentDate = date("Y-m-d");
        $defaultemail = Code::where('code','emailreceiver')->select('value')->get()->toarray();

        $result = DB::select('call spGetKelindanPermit();');
        $body = '';
        foreach($result as $r){
            $body = $body . '<b>Kelindan '.$r->name.'('.$r->employeeid.') Permit will be expired in '.$r->value.' day(s)</b><br>-Kelindan Name: '.$r->name.'<br>-Kelindan Employee ID: '.$r->employeeid.'<br>-Due Date: '.$r->permitdate.'<br><br>';
        }
        if($body != ''){
            foreach($defaultemail as $de){
                $mailstatus = app('App\Http\Controllers\sender')->email('All',$de['value'],'','Kelindan Permit Notification: '.$currentDate . ' - ' . env('APP_URL'),$body);
            }
            Log::error('checkKelindanPermit email status: '.response()->json($mailstatus));
            return response()->json($mailstatus);
        }else{
            Log::error('checkKelindanPermit email status: No Kelindan permit expired....');
            return 'No Kelindan permit expired....';
        }
    }

}
