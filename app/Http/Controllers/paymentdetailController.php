<?php

namespace App\Http\Controllers;

use App\DataTables\paymentdetailDataTable;
use App\Http\Requests;
use App\Http\Requests\CreatepaymentdetailRequest;
use App\Http\Requests\UpdatepaymentdetailRequest;
use App\Repositories\paymentdetailRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\Compound;
use App\Models\Claim;
use App\Models\Advance;
use App\Models\Loanpayment;
use App\Models\Loan;
use App\Models\paymentdetail;
use App\Models\Code;
use Illuminate\Support\Facades\Auth;
use DateTime;
use DateTimeZone;


class paymentdetailController extends AppBaseController
{
    /** @var paymentdetailRepository $paymentdetailRepository*/
    private $paymentdetailRepository;

    public function __construct(paymentdetailRepository $paymentdetailRepo)
    {
        $this->paymentdetailRepository = $paymentdetailRepo;
    }

    /**
     * Display a listing of the paymentdetail.
     *
     * @param paymentdetailDataTable $paymentdetailDataTable
     *
     * @return Response
     */
    public function index(paymentdetailDataTable $paymentdetailDataTable)
    {
        return $paymentdetailDataTable->render('paymentdetails.index');
    }

    /**
     * Show the form for creating a new paymentdetail.
     *
     * @return Response
     */
    public function create()
    {
        return redirect('/paymentdetails');
        // return view('paymentdetails.create');
    }

    /**
     * Store a newly created paymentdetail in storage.
     *
     * @param CreatepaymentdetailRequest $request
     *
     * @return Response
     */
    public function store(CreatepaymentdetailRequest $request)
    {
        return redirect('/paymentdetails');
        // $input = $request->all();

        // $paymentdetail = $this->paymentdetailRepository->create($input);

        // Flash::success('Paymentdetail saved successfully.');

        // return redirect(route('paymentdetails.index'));
    }

    /**
     * Display the specified paymentdetail.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        return redirect('/paymentdetails');
        // $paymentdetail = $this->paymentdetailRepository->find($id);

        // if (empty($paymentdetail)) {
        //     Flash::error('Paymentdetail not found');

        //     return redirect(route('paymentdetails.index'));
        // }

        // return view('paymentdetails.show')->with('paymentdetail', $paymentdetail);
    }

    /**
     * Show the form for editing the specified paymentdetail.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        return redirect('/paymentdetails');
        // $paymentdetail = $this->paymentdetailRepository->find($id);

        // if (empty($paymentdetail)) {
        //     Flash::error('Paymentdetail not found');

        //     return redirect(route('paymentdetails.index'));
        // }

        // return view('paymentdetails.edit')->with('paymentdetail', $paymentdetail);
    }

    /**
     * Update the specified paymentdetail in storage.
     *
     * @param int $id
     * @param UpdatepaymentdetailRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatepaymentdetailRequest $request)
    {
        return redirect('/paymentdetails');
        // $paymentdetail = $this->paymentdetailRepository->find($id);

        // if (empty($paymentdetail)) {
        //     Flash::error('Paymentdetail not found');

        //     return redirect(route('paymentdetails.index'));
        // }

        // $paymentdetail = $this->paymentdetailRepository->update($request->all(), $id);

        // Flash::success('Paymentdetail updated successfully.');

        // return redirect(route('paymentdetails.index'));
    }

    /**
     * Remove the specified paymentdetail from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $paymentdetail = $this->paymentdetailRepository->find($id);

        if (empty($paymentdetail)) {
            Flash::error('Paymentdetail not found');

            return redirect(route('paymentdetails.index'));
        }
        $Advance = Advance::whereIn('id',explode(",",$paymentdetail->adv_list))->update(['status'=>0]);
        $Claim = Claim::whereIn('id',explode(",",$paymentdetail->claim_list))->update(['status'=>0]);
        $Compound = Compound::whereIn('id',explode(",",$paymentdetail->comp_list))->update(['status'=>0]);
        $loanpayment = Loanpayment::whereIn('id',explode(",",$paymentdetail->loanpay_list))->select('loan_id')->get()->toArray();
        foreach($loanpayment as $loadpay){
            Loan::where('id',$loadpay['loan_id'])->update(['status'=>1]);
        }
        $loanpaymentdelete = Loanpayment::whereIn('id',explode(",",$paymentdetail->loanpay_list))->delete();
        $this->paymentdetailRepository->delete($id);

        Flash::success('Paymentdetail deleted successfully.');

        return redirect(route('paymentdetails.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewPaymentDetail('.Auth::id().',\'PaymentDetails\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = paymentdetail::destroy($ids);

        return $count;
    }

    public function massgenerate(Request $request)
    {

        $defaultemail = Code::where('code','emailreceiver')->select('value')->get()->toarray();
        $defaultemails = [];
        foreach($defaultemail as $de){
            array_push($defaultemails,$de['value']);
        }
        $user = Auth::user();
        $mailstatus = app('App\Http\Controllers\sender')->email('chajian@outlook.com', 'chajian@outlook.com', '','Notification Payment Generate - ','Generate Time: tst<br><br>');
        return response()->json($mailstatus);


        $currentTimeStamp = date("Y-m-d H:i:s");
        $data = $request->all();
        $datefrom = date_create($data['datefrom'])->format('Y-m-d');
        $dateto = date_create($data['dateto'])->format('Y-m-d');
        $month = date_create($data['datefrom'])->format('m');
        $driverlist = $this->implodeArrayofArrays(Driver::select('id')->get()->toArray());
        $result = DB::select('call spDriversPaymentDetails(\''.$datefrom.'\',\''.$dateto.'\',\''.$driverlist.'\',\''.$month.'\')');
        $error = '';
        $success = 0;
        $successlist = '';
        $fail = 0;
        $faillist = '';
        foreach ($result as $r) {
            $validate = DB::select('select id from paymentdetails where driver_id = \''.$r->id.'\' and datefrom <= \''.$dateto.'\' and \''.$datefrom.'\' <= dateto');
            if(count($validate) == 0){
                if($r->FinalAmount >= 0){
                    $input = null;
                    $input['driver_id'] = $r->id;
                    $input['datefrom'] = $datefrom;
                    $input['dateto'] = $dateto;
                    $input['month'] = $month;
                    $input['deduct_amount'] = $r->DeductAmount;
                    $input['final_amount'] = $r->FinalAmount;
                    $input['status'] = 1;
    
                    $input['do_amount'] = $r->a_sum;
                    $input['do_list'] = is_null($r->a_list)?'0':$r->a_list;
                    $input['do_report'] = DB::select('call spRPT_DriversCommission(\'2\',\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
    
                    $input['adv_amount'] = $r->b_sum;
                    $input['adv_list'] = is_null($r->b_list)?'0':$r->b_list;
                    $input['adv_report'] = DB::select('call spRPT_DriversAdvance(\'6\',\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
    
                    $input['claim_amount'] = $r->c_sum;
                    $input['claim_list'] = is_null($r->c_list)?'0':$r->c_list;
                    $input['claim_report'] = DB::select('call spRPT_DriversClaim(\'7\',\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
    
                    $input['comp_amount'] = $r->e_sum;
                    $input['comp_list'] = is_null($r->e_list)?'0':$r->e_list;
                    $input['comp_report'] = DB::select('call spRPT_DriversCompound(\'5\',\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
    
                    $input['bonus_amount'] = $r->g_sum;
                    $input['bonus_list'] = is_null($r->g_list)?'0':$r->g_list;
                    $input['bonus_report'] = DB::select('call spRPT_DriversBonus(\'12\',\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
    
                    $input['loanpay_amount'] = $r->f_sum;
                    // $input['loanpay_list'] = is_null($r->f_list)?'0':$r->f_list;
                    // DB::select('DROP TABLE IF EXISTS tem_spGetLoanByDriver;');
                    // $input['loanpay_report'] = DB::select('call spRPT_DriversLoan(\'10\',\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
                    $paymentdetail = $this->paymentdetailRepository->create($input);
                    DB::select('DROP TABLE IF EXISTS tem_spGetLoanByDriver;');
                    DB::select('call spGetLoanByDriver(\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\');');
                    $loandetails = DB::select('select * from tem_spGetLoanByDriver;');
                    $loanpayment_ids = [];
                    foreach ($loandetails as $loandetail) {
                        if($loandetail->payamount != 0){
                            $loanpaymentinput = null;
                            $loanpaymentinput['loan_id'] = $loandetail->id;
                            $loanpaymentinput['driver_id'] = $loandetail->driver_id;
                            $loanpaymentinput['date'] = date("Y-m-d");
                            $loanpaymentinput['description'] = 'SYSTEM GENERATED';
                            $loanpaymentinput['amount'] = $loandetail->payamount;
                            $loanpaymentinput['payment'] = 1;
                            $loanpaymentinput['source'] = 'P'.str_pad($paymentdetail->id, 10, '0', STR_PAD_LEFT);
                            $loanpayment = Loanpayment::create($loanpaymentinput);
                            if($loandetail->payamount == $loandetail->totaloutstanding){
                                $loan = Loan::where('id',$loandetail->id)->update(['status'=>9]);
                            }
                            array_push($loanpayment_ids,$loanpayment->id);
                        }
                    }
                    $loanpay_report = DB::select('call spRPT_DriversLoan_SPC(\'10\',\''.implode(",",$loanpayment_ids).'\')')[0]->ID;
                    $updatepaymentdetailloanlist = paymentdetail::where('id',$paymentdetail->id)->update(['loanpay_list'=>implode(",",$loanpayment_ids),'loanpay_report'=>$loanpay_report]);
                    $Advance = Advance::whereIn('id',explode(",",$input['adv_list']))->update(['status'=>1]);
                    $Claim = Claim::whereIn('id',explode(",",$input['claim_list']))->update(['status'=>1]);
                    $Compound = Compound::whereIn('id',explode(",",$input['comp_list']))->update(['status'=>1]);
    
                    $success = $success + 1;
                    $successlist = $successlist . 'Name: ' . $r->name . '<br>';
                    // $do_amount = $r->a_sum;
                    // $do_list = $r->a_list;
                    // $do_report = DB::select('call spRPT_DriversCommission(\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
    
                    // $adv_amount = $r->b_sum;
                    // $adv_list = $r->b_list;
                    // $adv_report = DB::select('call spRPT_DriversAdvance(\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
    
                    // $claim_amount = $r->c_sum;
                    // $claim_list = $r->c_list;
                    // $claim_report = DB::select('call spRPT_DriversClaim(\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
    
                    // $comp_amount = $r->e_sum;
                    // $comp_list = $r->e_list;
                    // $comp_report = DB::select('call spRPT_DriversCompound(\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
    
                    // $loanpay_amount = $r->f_sum;
                    // $loanpay_list = $r->f_list;
                    // $loanpay_report = DB::select('call spRPT_DriversLoan(\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
                }else{
                    $faillist = $faillist . 'Name: ' . $r->name . '<br>';
                    $fail = $fail + 1;
                }
            }else{
                $faillist = $faillist . 'Name: ' . $r->name . '<br>';
                $fail = $fail + 1;
            }

        }
        // Flash::error('Paymentdetail not found');
        // Flash::success('Paymentdetail deleted successfully.');
        if($success != 0 ){
            $error = $error . $success . ' driver(s) had success generated: <br>' . $successlist;
        }
        if($fail != 0 ){
            $error = $error . $fail . ' driver(s) had fail generated: <br>' . $faillist;
        }
        $defaultemail = Code::where('code','emailreceiver')->select('value')->get()->toarray();
        $defaultemails = [];
        foreach($defaultemail as $de){
            array_push($defaultemails,$de['value']);
        }
        $user = Auth::user();
        $mailstatus = app('App\Http\Controllers\sender')->email($user->name, $user->email, $defaultemails,'Notification Payment Mass Generate','Generate Time: '.$currentTimeStamp.'<br><br>'.$error);
        if($mailstatus){
            return response()->json('Email not send successfully.<br><br>'.$error);
        }
        return response()->json($error);
    }

    public function getGenerateDetails(Request $request)
    {
        $data = $request->all();
        $driverlist = $data['driver_id'];
        $datefrom = date_create($data['datefrom'])->format('Y-m-d');
        $dateto = date_create($data['dateto'])->format('Y-m-d');
        $month = date_create($data['datefrom'])->format('m');
        $result = DB::select('call spDriversPaymentDetails(\''.$datefrom.'\',\''.$dateto.'\',\''.$driverlist.'\',\''.$month.'\')')[0];
        // Flash::error('Paymentdetail not found');
        // Flash::success('Paymentdetail deleted successfully.');
        $error = $result->a_sum;
        $returndata = [];
        $returndata['driver_id'] = $driverlist;
        $returndata['datefrom'] = $datefrom;
        $returndata['dateto'] = $dateto;
        $returndata['do_amount'] = $result->a_sum - $result->DeductAmount;
        $returndata['loanpay_amount'] = number_format($result->f_sum,2);
        $claim_data = DB::select('select sum(coalesce(amount,0)) as "amount" from claims where status = 0 and driver_id='.$driverlist.' and date <= \''.$dateto.'\' and date >= \''.$datefrom.'\' group by driver_id, status');
        $returndata['claim_data'] = isset($claim_data[0]->amount) ? $claim_data[0]->amount : null;
        $returndata['compound_data'] = DB::select('select id, DATE_FORMAT(date, "%d-%m-%Y") as "date", no, amount from compounds where status = 0 and driver_id='.$driverlist);
        $returndata['advance_data'] = DB::select('select id, DATE_FORMAT(date, "%d-%m-%Y") as "date", no, amount from advances where status = 0 and driver_id='.$driverlist);
        $returndata['bonus_amount'] = $result->g_sum;
        DB::select('call spGetLoanByDriver(\''.$datefrom.'\',\''.$dateto.'\',\''.$driverlist.'\');');
        $returndata['loan_data'] = DB::select('select * from tem_spGetLoanByDriver;');
        return response()->json($returndata);
        // return response()->json(['message' => 'generate'], 404);
    }

    public function generate(Request $request)
    {
        $currentTimeStamp = date("Y-m-d H:i:s");
        $data = $request->all()['data'];
        $driver_id = $data['driver_id'];
        $datefrom = $data['datefrom'];
        $dateto = $data['dateto'];
        $month = date_create($data['datefrom'])->format('m');
        $loan_data = isset($data['loan_data']) ? $data['loan_data'] : null;
        $compound_data = isset($data['compound_data']) ? implode(",",$data['compound_data']) : '';
        $advance_data = isset($data['advance_data']) ? implode(",",$data['advance_data']) : '';
        $final_amount = 0;

        
        $result = DB::select('call spDriversPaymentDetails(\''.$datefrom.'\',\''.$dateto.'\',\''.$driver_id.'\',\''.$month.'\')');
        $error = '';
        $success = 0;
        $successlist = '';
        $fail = 0;
        $faillist = '';
        foreach ($result as $r) {
            $validate = DB::select('select id from paymentdetails where driver_id = \''.$r->id.'\' and datefrom <= \''.$dateto.'\' and \''.$datefrom.'\' <= dateto');
            if(count($validate) == 0){
                if($r->FinalAmount >= 0){
                    $input = null;
                    $input['driver_id'] = $r->id;
                    $input['datefrom'] = $datefrom;
                    $input['dateto'] = $dateto;
                    $input['month'] = $month;
                    $input['deduct_amount'] = $r->DeductAmount;
                    // $input['final_amount'] = $r->FinalAmount;
                    $input['status'] = 1;
                    $final_amount = $final_amount +$r->a_sum;
                    $input['do_amount'] = $r->a_sum;
                    $input['do_list'] = is_null($r->a_list)?'0':$r->a_list;
                    $input['do_report'] = DB::select('call spRPT_DriversCommission(\'2\',\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
                    $final_amount = + $final_amount + $r->c_sum;
                    $input['claim_amount'] = $r->c_sum;
                    $input['claim_list'] = is_null($r->c_list)?'0':$r->c_list;
                    $input['claim_report'] = DB::select('call spRPT_DriversClaim(\'7\',\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;
                    
                    $adv_amount = DB::select('select sum(amount) as amount from advances where status = 0 and driver_id = \''.$r->id.'\' and date <= \''.$dateto.'\' and \''.$datefrom.'\' <= date and FIND_IN_SET(id,\''.$advance_data.'\')')[0]->amount;
                    $final_amount = $final_amount - $adv_amount;
                    $input['adv_amount'] = $adv_amount;
                    $input['adv_list'] = $advance_data;
                    $input['adv_report'] = DB::select('call spRPT_DriversAdvance_SPC(\'6\',\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\',\''.$advance_data.'\')')[0]->ID;
    
                    $comp_amount = DB::select('select sum(amount) as amount from compounds where status = 0 and driver_id = \''.$r->id.'\' and date <= \''.$dateto.'\' and \''.$datefrom.'\' <= date and FIND_IN_SET(id,\''.$compound_data.'\')')[0]->amount;
                    $final_amount = $final_amount - $comp_amount;
                    $input['comp_amount'] = $comp_amount;
                    $input['comp_list'] = $compound_data;
                    $input['comp_report'] = DB::select('call spRPT_DriversCompound_SPC(\'5\',\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\',\''.$compound_data.'\')')[0]->ID;
    
                    $final_amount = + $final_amount + $r->g_sum;
                    $input['bonus_amount'] = $r->g_sum;
                    $input['bonus_list'] = is_null($r->g_list)?'0':$r->g_list;
                    $input['bonus_report'] = DB::select('call spRPT_DriversBonus(\'12\',\''.$datefrom.'\',\''.$dateto.'\',\''.$r->id.'\')')[0]->ID;

                    $paymentdetail = $this->paymentdetailRepository->create($input);
    
                    $loan_amount = 0;
                    $loan_list = [];
                    $loanpayment_ids = [];
                    if($loan_data != null){
                        foreach ($loan_data as $ld) {
                            $ld_json = json_decode($ld);
                            $loan_amount = $loan_amount + $ld_json->amount;
                            array_push($loan_list,$ld_json->id);
    
                            $loanpaymentinput = null;
                            $loanpaymentinput['loan_id'] = $ld_json->id;
                            $loanpaymentinput['date'] = date("Y-m-d");
                            $loanpaymentinput['description'] = 'SYSTEM GENERATED';
                            $loanpaymentinput['amount'] = $ld_json->amount;
                            $loanpaymentinput['payment'] = 1;
                            $loanpaymentinput['source'] = 'P'.str_pad($paymentdetail->id, 10, '0', STR_PAD_LEFT);
                            $loanpayment = Loanpayment::create($loanpaymentinput);
                            $loanoutstanding = DB::select('select l.id,(l.totalamount - COALESCE(SUM(lp.amount),0)) as outstanding from loans l left join loanpayments lp on l.id=lp.loan_id and lp.payment = 1 where l.id='.$ld_json->id.' group by l.id,l.totalamount')[0]->outstanding;
                            if($loanoutstanding == 0){
                                $loan = Loan::where('id',$ld_json->id)->update(['status'=>9]);
                            }
                            array_push($loanpayment_ids,$loanpayment->id);
                        }
                    }
                    $loanpay_report = DB::select('call spRPT_DriversLoan_SPC(\'10\',\''.implode(",",$loanpayment_ids).'\')')[0]->ID;
                    $final_amount = $final_amount - $loan_amount - $r->DeductAmount;
                    $updatepaymentdetailloanlist = paymentdetail::where('id',$paymentdetail->id)->update(['loanpay_list'=>implode(",",$loanpayment_ids),'loanpay_amount'=>$loan_amount,'loanpay_report'=>$loanpay_report,'final_amount'=>$final_amount]);
                    $Advance = Advance::whereIn('id',explode(",",$input['adv_list']))->update(['status'=>1]);
                    $Claim = Claim::whereIn('id',explode(",",$input['claim_list']))->update(['status'=>1]);
                    $Compound = Compound::whereIn('id',explode(",",$input['comp_list']))->update(['status'=>1]);
    
                    $success = $success + 1;
                    $successlist = $successlist . 'Name: ' . $r->name . '<br>';
                }else{
                    $faillist = $faillist . 'Name: ' . $r->name . '<br>';
                    $fail = $fail + 1;
                }
            }else{
                $faillist = $faillist . 'Name: ' . $r->name . '<br>';
                $fail = $fail + 1;
            }

        }
        // Flash::error('Paymentdetail not found');
        // Flash::success('Paymentdetail deleted successfully.');
        if($success != 0 ){
            $error = $error . $success . ' driver(s) had success generated: <br>' . $successlist;
        }
        if($fail != 0 ){
            $error = $error . $fail . ' driver(s) had fail generated: <br>' . $faillist;
        }
        $defaultemail = Code::where('code','emailreceiver')->select('value')->get()->toarray();
        $defaultemails = [];
        foreach($defaultemail as $de){
            array_push($defaultemails,$de['value']);
        }
        $user = Auth::user();
        $mailstatus = app('App\Http\Controllers\sender')->email($user->name, $user->email, $defaultemails,'Notification Payment Generate - ' . env('APP_URL'),'Generate Time: '.$currentTimeStamp.'<br><br>'.$error);
        if($mailstatus){
            return response()->json('Email not send successfully.<br><br>'.$error);
        }
        return response()->json($error);
        // return response()->json(['message' => 'generate'], 404);
    }

    function implodeArrayofArrays($array, $glue  = ',') {
        $output = '';
        foreach ($array as $subarray) {
            $output .= $subarray['id'].$glue;
        }
        return substr($output,0,strlen($output)-1);
    }

}
