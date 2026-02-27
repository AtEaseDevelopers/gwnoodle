<?php

namespace App\Http\Controllers;

use App\DataTables\LoanDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Repositories\LoanRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Loanpayment;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanController extends AppBaseController
{
    /** @var LoanRepository $loanRepository*/
    private $loanRepository;

    public function __construct(LoanRepository $loanRepo)
    {
        $this->loanRepository = $loanRepo;
    }

    /**
     * Display a listing of the Loan.
     *
     * @param LoanDataTable $loanDataTable
     *
     * @return Response
     */
    public function index(LoanDataTable $loanDataTable)
    {
        return $loanDataTable->render('loans.index');
    }

    /**
     * Show the form for creating a new Loan.
     *
     * @return Response
     */
    public function create()
    {
        return view('loans.create');
    }

    /**
     * Store a newly created Loan in storage.
     *
     * @param CreateLoanRequest $request
     *
     * @return Response
     */
    public function store(CreateLoanRequest $request)
    {
        $input = $request->all();
        $input['monthlyamount'] = round((($input['amount']+($input['amount'] * ($input['period']*$input['rate']/12) / 100)) / $input['period']),2);
        $input['totalamount'] = round(($input['monthlyamount'] * $input['period']),2);
        $input['date'] = date_create($input['date']);

        $loan = $this->loanRepository->create($input);

        Flash::success('Loan saved successfully.');

        return redirect(route('loans.index'));
    }

    /**
     * Display the specified Loan.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $loan = $this->loanRepository->find($id);

        if (empty($loan)) {
            Flash::error('Loan not found');

            return redirect(route('loans.index'));
        }

        return view('loans.show')->with('loan', $loan);
    }

    /**
     * Show the form for editing the specified Loan.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $loan = $this->loanRepository->find($id);

        if (empty($loan)) {
            Flash::error('Loan not found');

            return redirect(route('loans.index'));
        }

        if ($loan->status == 1) {
            Flash::error('Unable to edit Loan, Loan is being started');

            return redirect(route('loans.index'));
        }

        if ($loan->status == 9) {
            Flash::error('Unable to edit Loan, Loan is being closed');

            return redirect(route('loans.index'));
        }

        return view('loans.edit')->with('loan', $loan);
    }

    /**
     * Update the specified Loan in storage.
     *
     * @param int $id
     * @param UpdateLoanRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateLoanRequest $request)
    {
        $id = Crypt::decrypt($id);
        $loan = $this->loanRepository->find($id);

        if (empty($loan)) {
            Flash::error('Loan not found');

            return redirect(route('loans.index'));
        }

        $input = $request->all();
        $input['monthlyamount'] = round((($input['amount']+($input['amount'] * ($input['period']*$input['rate']/12) / 100)) / $input['period']),2);
        $input['totalamount'] = round(($input['monthlyamount'] * $input['period']),2);
        $input['date'] = date_create($input['date']);

        $loan = $this->loanRepository->update($input, $id);

        Flash::success('Loan updated successfully.');

        return redirect(route('loans.index'));
    }

    /**
     * Remove the specified Loan from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $loan = $this->loanRepository->find($id);

        if (empty($loan)) {
            Flash::error('Loan not found');

            return redirect(route('loans.index'));
        }

        $loanpayment = Loanpayment::where('loan_id',$id)->get()->toArray();
        if(count($loanpayment)>0){
            Flash::error('Unable to delete Loan, Loan is being used in Loan Payment');

            return redirect(route('loans.index'));
        }

        if ($loan->status == 1) {
            Flash::error('Unable to delete Loan, Loan is being started');

            return redirect(route('loans.index'));
        }

        if ($loan->status == 1) {
            Flash::error('Unable to delete Loan, Loan is being closed');

            return redirect(route('loans.index'));
        }

        $this->loanRepository->delete($id);

        Flash::success('Loan deleted successfully.');

        return redirect(route('loans.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewLoan('.Auth::id().',\'Loans\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    /**
     * Remove the specified Loan from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function start($id)
    {
        $id = Crypt::decrypt($id);
        $loan = $this->loanRepository->find($id);

        if (empty($loan)) {
            Flash::error('Loan not found');

            return redirect(route('loans.index'));
        }

        if($loan->status == 1){
            Flash::error('Loan already started');

            return redirect(route('loans.index'));
        }

        if($loan->status == 9){
            Flash::error('Loan already closed');

            return redirect(route('loans.index'));
        }

        $loan->status = 1;
        $loan->save();

        Flash::success('Loan started successfully.');

        return redirect(route('loans.index'));
    }
}
