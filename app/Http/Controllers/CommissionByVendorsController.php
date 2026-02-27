<?php

namespace App\Http\Controllers;

use App\DataTables\CommissionByVendorsDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateCommissionByVendorsRequest;
use App\Http\Requests\UpdateCommissionByVendorsRequest;
use App\Repositories\CommissionByVendorsRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use App\Models\CommissionByVendors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CommissionByVendorsController extends AppBaseController
{
    /** @var CommissionByVendorsRepository $commissionByVendorsRepository*/
    private $commissionByVendorsRepository;

    public function __construct(CommissionByVendorsRepository $commissionByVendorsRepo)
    {
        $this->commissionByVendorsRepository = $commissionByVendorsRepo;
    }

    /**
     * Display a listing of the CommissionByVendors.
     *
     * @param CommissionByVendorsDataTable $commissionByVendorsDataTable
     *
     * @return Response
     */
    public function index(CommissionByVendorsDataTable $commissionByVendorsDataTable)
    {
        return $commissionByVendorsDataTable->render('commission_by_vendors.index');
    }

    /**
     * Show the form for creating a new CommissionByVendors.
     *
     * @return Response
     */
    public function create()
    {
        return view('commission_by_vendors.create');
    }

    /**
     * Store a newly created CommissionByVendors in storage.
     *
     * @param CreateCommissionByVendorsRequest $request
     *
     * @return Response
     */
    public function store(CreateCommissionByVendorsRequest $request)
    {
        $input = $request->all();

        $commissionByVendors = $this->commissionByVendorsRepository->create($input);

        Flash::success('Commission By Vendors saved successfully.');

        return redirect(route('commissionByVendors.index'));
    }

    /**
     * Display the specified CommissionByVendors.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $commissionByVendors = $this->commissionByVendorsRepository->find($id);

        if (empty($commissionByVendors)) {
            Flash::error('Commission By Vendors not found');

            return redirect(route('commissionByVendors.index'));
        }

        return view('commission_by_vendors.show')->with('commissionByVendors', $commissionByVendors);
    }

    /**
     * Show the form for editing the specified CommissionByVendors.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $commissionByVendors = $this->commissionByVendorsRepository->find($id);

        if (empty($commissionByVendors)) {
            Flash::error('Commission By Vendors not found');

            return redirect(route('commissionByVendors.index'));
        }

        return view('commission_by_vendors.edit')->with('commissionByVendors', $commissionByVendors);
    }

    /**
     * Update the specified CommissionByVendors in storage.
     *
     * @param int $id
     * @param UpdateCommissionByVendorsRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateCommissionByVendorsRequest $request)
    {
        $id = Crypt::decrypt($id);
        $commissionByVendors = $this->commissionByVendorsRepository->find($id);

        if (empty($commissionByVendors)) {
            Flash::error('Commission By Vendors not found');

            return redirect(route('commissionByVendors.index'));
        }

        $commissionByVendors = $this->commissionByVendorsRepository->update($request->all(), $id);

        Flash::success('Commission By Vendors updated successfully.');

        return redirect(route('commissionByVendors.index'));
    }

    /**
     * Remove the specified CommissionByVendors from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $commissionByVendors = $this->commissionByVendorsRepository->find($id);

        if (empty($commissionByVendors)) {
            Flash::error('Commission By Vendors not found');

            return redirect(route('commissionByVendors.index'));
        }

        $this->commissionByVendorsRepository->delete($id);

        Flash::success('Commission By Vendors deleted successfully.');

        return redirect(route('commissionByVendors.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewCommissionByVendor('.Auth::id().',\'COMByVendors\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = CommissionByVendors::destroy($ids);

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = CommissionByVendors::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
}
