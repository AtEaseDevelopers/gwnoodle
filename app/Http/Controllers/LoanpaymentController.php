<?php

namespace App\Http\Controllers;

use App\DataTables\LoanpaymentDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateLoanpaymentRequest;
use App\Http\Requests\UpdateLoanpaymentRequest;
use App\Repositories\LoanpaymentRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Loan;
use App\Models\Loanpayment;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanpaymentController extends AppBaseController
{
    /** @var LoanpaymentRepository $loanpaymentRepository*/
    private $loanpaymentRepository;

    public function __construct(LoanpaymentRepository $loanpaymentRepo)
    {
        $this->loanpaymentRepository = $loanpaymentRepo;
    }

    /**
     * Display a listing of the Loanpayment.
     *
     * @param LoanpaymentDataTable $loanpaymentDataTable
     *
     * @return Response
     */
    public function index(LoanpaymentDataTable $loanpaymentDataTable)
    {
        return $loanpaymentDataTable->render('loanpayments.index');
    }

    /**
     * Show the form for creating a new Loanpayment.
     *
     * @return Response
     */
    public function create()
    {
        return view('loanpayments.create');
    }

    /**
     * Store a newly created Loanpayment in storage.
     *
     * @param CreateLoanpaymentRequest $request
     *
     * @return Response
     */
    public function store(CreateLoanpaymentRequest $request)
    {
        $input = $request->all();

        $vldresult = $this->ValidatePayment($input['amount'],$input['loan_id'],0);
        if($input['payment']==1){
            if($vldresult == -1){
                return Redirect::back()->withInput($input)->withErrors('Payment amount cannot more than Loan Oustanding amount');
            }
        }

        $input['date'] = date_create($input['date']);
        $loanpayment = $this->loanpaymentRepository->create($input);


        $loan = Loan::where('id',$input['loan_id'])->update(['status'=>$vldresult]);
        
        Flash::success('Loan Payment saved successfully.');

        return redirect(route('loanpayments.index'));
    }

    /**
     * Display the specified Loanpayment.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $loanpayment = $this->loanpaymentRepository->find($id);

        if (empty($loanpayment)) {
            Flash::error('Loan Payment not found');

            return redirect(route('loanpayments.index'));
        }

        return view('loanpayments.show')->with('loanpayment', $loanpayment);
    }

    /**
     * Show the form for editing the specified Loanpayment.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $loanpayment = $this->loanpaymentRepository->find($id);

        if (empty($loanpayment)) {
            Flash::error('Loan Payment not found');

            return redirect(route('loanpayments.index'));
        }

        return view('loanpayments.edit')->with('loanpayment', $loanpayment);
    }

    /**
     * Update the specified Loanpayment in storage.
     *
     * @param int $id
     * @param UpdateLoanpaymentRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateLoanpaymentRequest $request)
    {
        $id = Crypt::decrypt($id);
        $loanpayment = $this->loanpaymentRepository->find($id);

        if (empty($loanpayment)) {
            Flash::error('Loan Payment not found');

            return redirect(route('loanpayments.index'));
        }

        $input = $request->all();

        $vldresult = $this->ValidatePayment($input['amount'],$input['loan_id'],$id);
        if($input['payment']==1){
            if($vldresult == -1){
                return Redirect::back()->withInput($input)->withErrors('Payment amount cannot more than Loan Oustanding amount');
            }else{
                $loan = Loan::where('id',$input['loan_id'])->update(['status'=>$vldresult]);
            }
        }else{
            $loan = Loan::where('id',$input['loan_id'])->update(['status'=>1]);
        }
        
        $input['date'] = date_create($input['date']);
        $loanpayment = $this->loanpaymentRepository->update($input, $id);

        Flash::success('Loan Payment updated successfully.');

        return redirect(route('loanpayments.index'));
    }

    /**
     * Remove the specified Loanpayment from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $loanpayment = $this->loanpaymentRepository->find($id);

        if (empty($loanpayment)) {
            Flash::error('Loan Payment not found');

            return redirect(route('loanpayments.index'));
        }

        if ($loanpayment->payment == 1) {
            Flash::error('Unable to delete Loan Payment, please unpaid first');

            return redirect(route('loanpayments.index'));
        }

        $loan_id = $loanpayment->loan_id;

        $this->loanpaymentRepository->delete($id);

        $loanpaymentlist = Loanpayment::where('loan_id',$loan_id)->get()->toarray();

        if (empty($loanpaymentlist)) {
            $loan = Loan::where('id',$loan_id)->update(['status'=>0]);
        }

        Flash::success('Loan Payment deleted successfully.');

        return redirect(route('loanpayments.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewLoanPayment('.Auth::id().',\'LoanPayments\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = 0;

        foreach ($ids as $id) {

            $loanpayment = $this->loanpaymentRepository->find($id);

            $loan_id = $loanpayment->loan_id;

            $count = $count + Loanpayment::where('payment','0')->where('id',$id)->delete();

            $loanpaymentlist = Loanpayment::where('loan_id',$loan_id)->get()->toarray();
    
            if (empty($loanpaymentlist)) {
                $loan = Loan::where('id',$loan_id)->update(['status'=>0]);
            }

        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $payment = $data['status'];
        
        $count = Loanpayment::whereIn('id',$ids)->update(['payment'=>$payment]);

        return $count;
    }
    
    public function ValidatePayment($amount,$loan_id,$id)
    {
        try{
            $result = DB::select('select l.id,(l.totalamount - COALESCE(SUM(lp.amount),0)) as outstanding from loans l left join loanpayments lp on l.id=lp.loan_id and lp.payment = 1 and lp.id<>'.$id.' where l.status in (1,9) and l.id='.$loan_id.' group by l.id,l.totalamount')[0];
            if($amount < $result->outstanding){
                return 1;
            }else if($amount == $result->outstanding){
                return 9;
            }else{
                return -1;
            }
        }catch (Exception $e){
            return 1;
        }
    }

    public function getPaymentDetails(Request $request)
    {
        $data = $request->all();

        $loan_id = $data['loanid'];
        $result = DB::select('select DATE_FORMAT(date, "%d-%m-%Y") as date,description,source,format(amount,2) as amount from loanpayments where payment = 1 and loan_id='.$loan_id);
        return $result;
    }
}
