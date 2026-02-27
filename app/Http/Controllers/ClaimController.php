<?php

namespace App\Http\Controllers;

use App\DataTables\ClaimDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateClaimRequest;
use App\Http\Requests\UpdateClaimRequest;
use App\Repositories\ClaimRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Code;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Claim;
use Illuminate\Support\Facades\Auth;


class ClaimController extends AppBaseController
{
    /** @var ClaimRepository $claimRepository*/
    private $claimRepository;

    public function __construct(ClaimRepository $claimRepo)
    {
        $this->claimRepository = $claimRepo;
    }

    /**
     * Display a listing of the Claim.
     *
     * @param ClaimDataTable $claimDataTable
     *
     * @return Response
     */
    public function index(ClaimDataTable $claimDataTable)
    {
        return $claimDataTable->render('claims.index');
    }

    /**
     * Show the form for creating a new Claim.
     *
     * @return Response
     */
    public function create()
    {
        return view('claims.create');
    }

    /**
     * Store a newly created Claim in storage.
     *
     * @param CreateClaimRequest $request
     *
     * @return Response
     */
    public function store(CreateClaimRequest $request)
    {
        $input = $request->all();

        if($input['no'] == null){
            $claim_runningno = Code::where('code','claim_runningno')->select('value')->get()[0]['value'];
            $input['no'] = 'C'.date("y").date("m").date("d").'_'.$claim_runningno;
            Code::where('code','claim_runningno')->update(['value' => $claim_runningno + 1]);
        }

        $input['date'] = date_create($input['date']);
        $claim = $this->claimRepository->create($input);

        Flash::success('Claim saved successfully.');

        return redirect(route('claims.index'));
    }

    /**
     * Display the specified Claim.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $claim = $this->claimRepository->find($id);

        if (empty($claim)) {
            Flash::error('Claim not found');

            return redirect(route('claims.index'));
        }

        return view('claims.show')->with('claim', $claim);
    }

    /**
     * Show the form for editing the specified Claim.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $claim = $this->claimRepository->find($id);

        if (empty($claim)) {
            Flash::error('Claim not found');

            return redirect(route('claims.index'));
        }

        if($claim->editable == 0) {
            Flash::error('Claim '.$claim->no.' cannot be edit');

            return redirect(route('claims.index'));
        }

        return view('claims.edit')->with('claim', $claim);
    }

    /**
     * Update the specified Claim in storage.
     *
     * @param int $id
     * @param UpdateClaimRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateClaimRequest $request)
    {
        $id = Crypt::decrypt($id);
        $claim = $this->claimRepository->find($id);

        if (empty($claim)) {
            Flash::error('Claim not found');

            return redirect(route('claims.index'));
        }

        $input = $request->all();

        $input['date'] = date_create($input['date']);
        $claim = $this->claimRepository->update($input, $id);

        Flash::success('Claim updated successfully.');

        return redirect(route('claims.index'));
    }

    /**
     * Remove the specified Claim from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $claim = $this->claimRepository->find($id);

        if (empty($claim)) {
            Flash::error('Claim not found');

            return redirect(route('claims.index'));
        }

        $this->claimRepository->delete($id);

        Flash::success('Claim deleted successfully.');

        return redirect(route('claims.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewClaim('.Auth::id().',\'Claims\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = Claim::destroy($ids);

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = Claim::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }

    public function getDOList(Request $request)
    {
        $data = $request->all();
        $driver_id = $data['driver_id'];
        $lorry_id = $data['lorry_id'];
        $result = DB::select('select dono, id from deliveryorders where driver_id = \''.$driver_id.'\' and lorry_id = \''.$lorry_id.'\'');
        return $result;
    }
}
