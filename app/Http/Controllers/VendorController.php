<?php

namespace App\Http\Controllers;

use App\DataTables\VendorDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Repositories\VendorRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Price;
use App\Models\Bonus;
use App\Models\DeliveryOrder;
use App\Models\CommissionByVendors;
use Illuminate\Http\Request;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class VendorController extends AppBaseController
{
    /** @var VendorRepository $vendorRepository*/
    private $vendorRepository;

    public function __construct(VendorRepository $vendorRepo)
    {
        $this->vendorRepository = $vendorRepo;
    }

    /**
     * Display a listing of the Vendor.
     *
     * @param VendorDataTable $vendorDataTable
     *
     * @return Response
     */
    public function index(VendorDataTable $vendorDataTable)
    {
        return $vendorDataTable->render('vendors.index');
    }

    /**
     * Show the form for creating a new Vendor.
     *
     * @return Response
     */
    public function create()
    {
        return view('vendors.create');
    }

    /**
     * Store a newly created Vendor in storage.
     *
     * @param CreateVendorRequest $request
     *
     * @return Response
     */
    public function store(CreateVendorRequest $request)
    {
        $input = $request->all();

        $vendor = $this->vendorRepository->create($input);

        Flash::success($input['code'].' saved successfully.');

        return redirect(route('vendors.index'));
    }

    /**
     * Display the specified Vendor.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $vendor = $this->vendorRepository->find($id);

        if (empty($vendor)) {
            Flash::error('Vendor not found');

            return redirect(route('vendors.index'));
        }

        return view('vendors.show')->with('vendor', $vendor);
    }

    /**
     * Show the form for editing the specified Vendor.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $vendor = $this->vendorRepository->find($id);

        if (empty($vendor)) {
            Flash::error('Vendor not found');

            return redirect(route('vendors.index'));
        }

        return view('vendors.edit')->with('vendor', $vendor);
    }

    /**
     * Update the specified Vendor in storage.
     *
     * @param int $id
     * @param UpdateVendorRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateVendorRequest $request)
    {
        $id = Crypt::decrypt($id);
        $vendor = $this->vendorRepository->find($id);

        if (empty($vendor)) {
            Flash::error('Vendor not found');

            return redirect(route('vendors.index'));
        }

        $vendor = $this->vendorRepository->update($request->all(), $id);

        Flash::success($vendor->code.' updated successfully.');

        return redirect(route('vendors.index'));
    }

    /**
     * Remove the specified Vendor from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $vendor = $this->vendorRepository->find($id);

        if (empty($vendor)) {
            Flash::error('Vendor not found');

            return redirect(route('vendors.index'));
        }

        $CommissionByVendors = CommissionByVendors::where('vendor_id',$id)->get()->toArray();
        if(count($CommissionByVendors)>0){
            Flash::error('Unable to delete '.$vendor->code.', '.$vendor->code.' is being used in Commission By Vendors');

            return redirect(route('vendors.index'));
        }

        $deliveryorder = DeliveryOrder::where('vendor_id',$id)->get()->toArray();
        if(count($deliveryorder)>0){
            Flash::error('Unable to delete '.$vendor->code.', '.$vendor->code.' is being used in Delivery Order');

            return redirect(route('vendors.index'));
        }

        $price = Price::where('vendor_id',$id)->get()->toArray();
        if(count($price)>0){
            Flash::error('Unable to delete '.$vendor->code.', '.$vendor->code.' is being used in Price');

            return redirect(route('vendors.index'));
        }

        $bonus = Bonus::where('vendor_id',$id)->get()->toArray();
        if(count($bonus)>0){
            Flash::error('Unable to delete '.$vendor->code.', '.$vendor->code.' is being used in Bonus');

            return redirect(route('vendors.index'));
        }

        $this->vendorRepository->delete($id);

        Flash::success($vendor->code.' deleted successfully.');

        return redirect(route('vendors.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewVendor('.Auth::id().',\'Vendors\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = 0;

        foreach ($ids as $id) {

            $CommissionByVendors = CommissionByVendors::where('vendor_id',$id)->get()->toArray();
            if(count($CommissionByVendors)>0){
                continue;
            }

            $deliveryorder = DeliveryOrder::where('vendor_id',$id)->get()->toArray();
            if(count($deliveryorder)>0){
                continue;
            }
    
            $price = Price::where('vendor_id',$id)->get()->toArray();
            if(count($price)>0){
                continue;
            }

            $bonus = Bonus::where('vendor_id',$id)->get()->toArray();
            if(count($bonus)>0){
                continue;
            }

            $count = $count + Vendor::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = Vendor::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
}
