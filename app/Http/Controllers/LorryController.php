<?php

namespace App\Http\Controllers;

use App\DataTables\LorryDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateLorryRequest;
use App\Http\Requests\UpdateLorryRequest;
use App\Repositories\LorryRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use App\Models\Lorry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\Trip;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransfer;
use App\Models\servicedetails;

class LorryController extends AppBaseController
{
    /** @var LorryRepository $lorryRepository*/
    private $lorryRepository;

    public function __construct(LorryRepository $lorryRepo)
    {
        $this->lorryRepository = $lorryRepo;
    }

    /**
     * Display a listing of the Lorry.
     *
     * @param LorryDataTable $lorryDataTable
     *
     * @return Response
     */
    public function index(LorryDataTable $lorryDataTable)
    {
        return $lorryDataTable->render('lorries.index');
    }

    /**
     * Show the form for creating a new Lorry.
     *
     * @return Response
     */
    public function create()
    {
        return view('lorries.create');
    }

    /**
     * Store a newly created Lorry in storage.
     *
     * @param CreateLorryRequest $request
     *
     * @return Response
     */
    public function store(CreateLorryRequest $request)
    {
        $input = $request->all();

        $lorry = $this->lorryRepository->create($input);

        Flash::success($input['lorryno'].__('lorries.saved_successfully'));

        return redirect(route('lorries.index'));
    }

    /**
     * Display the specified Lorry.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $lorry = $this->lorryRepository->find($id);

        if (empty($lorry)) {
            Flash::error(__('lorries.lorry_not_found'));

            return redirect(route('lorries.index'));
        }

        return view('lorries.show')->with('lorry', $lorry);
    }

    /**
     * Show the form for editing the specified Lorry.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $lorry = $this->lorryRepository->find($id);

        if (empty($lorry)) {
            Flash::error(__('lorries.lorry_not_found'));

            return redirect(route('lorries.index'));
        }

        return view('lorries.edit')->with('lorry', $lorry);
    }

    /**
     * Update the specified Lorry in storage.
     *
     * @param int $id
     * @param UpdateLorryRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateLorryRequest $request)
    {
        $id = Crypt::decrypt($id);
        $lorry = $this->lorryRepository->find($id);

        if (empty($lorry)) {
            Flash::error(__('lorries.lorry_not_found'));

            return redirect(route('lorries.index'));
        }

        $lorry = $this->lorryRepository->update($request->all(), $id);

        Flash::success($lorry->lorryno.__('lorries.updated_successfully'));

        return redirect(route('lorries.index'));
    }

    /**
     * Remove the specified Lorry from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $lorry = $this->lorryRepository->find($id);

        if (empty($lorry)) {
            Flash::error(__('lorries.lorry_not_found'));

            return redirect(route('lorries.index'));
        }

        $Trip = Trip::where('lorry_id',$id)->get()->toArray();
        if(count($Trip)>0){
            Flash::error('Unable to delete '.$lorry->lorryno.', '.$lorry->lorryno.' is being used in Trip');

            return redirect(route('lorries.index'));
        }

        $Invoice = Invoice::where('lorry_id',$id)->get()->toArray();
        if(count($Invoice)>0){
            Flash::error('Unable to delete '.$lorry->lorryno.', '.$lorry->lorryno.' is being used in Invoice');

            return redirect(route('lorries.index'));
        }

        $InventoryBalance = InventoryBalance::where('lorry_id',$id)->get()->toArray();
        if(count($InventoryBalance)>0){
            Flash::error('Unable to delete '.$lorry->lorryno.', '.$lorry->lorryno.' is being used in Inventory Balance');

            return redirect(route('lorries.index'));
        }

        $InventoryTransaction = InventoryTransaction::where('lorry_id',$id)->get()->toArray();
        if(count($InventoryTransaction)>0){
            Flash::error('Unable to delete '.$lorry->lorryno.', '.$lorry->lorryno.' is being used in Inventory Transaction');

            return redirect(route('lorries.index'));
        }

        $InventoryTransfer = InventoryTransfer::where('from_lorry_id',$id)->orWhere('to_lorry_id',$id)->get()->toArray();
        if(count($InventoryTransfer)>0){
            Flash::error('Unable to delete '.$lorry->lorryno.', '.$lorry->lorryno.' is being used in Inventory Transfer');

            return redirect(route('lorries.index'));
        }

        $servicedetails = servicedetails::where('from_lorry_id',$id)->orWhere('to_lorry_id',$id)->get()->toArray();
        if(count($servicedetails)>0){
            Flash::error('Unable to delete '.$lorry->lorryno.', '.$lorry->lorryno.' is being used in Lorry Service');

            return redirect(route('lorries.index'));
        }

        $this->lorryRepository->delete($id);

        Flash::success($lorry->lorryno.__('lorries.deleted_successfully'));

        return redirect(route('lorries.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $result = DB::select('CALL spViewLorry('.Auth::id().',\'Lorries\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {

            $Trip = Trip::where('lorry_id',$id)->get()->toArray();
            if(count($Trip)>0){
                continue;
            }

            $Invoice = Invoice::where('lorry_id',$id)->get()->toArray();
            if(count($Invoice)>0){
                continue;
            }

            $InventoryBalance = InventoryBalance::where('lorry_id',$id)->get()->toArray();
            if(count($InventoryBalance)>0){
                continue;
            }

            $InventoryTransaction = InventoryTransaction::where('lorry_id',$id)->get()->toArray();
            if(count($InventoryTransaction)>0){
                continue;
            }

            $InventoryTransfer = InventoryTransfer::where('from_lorry_id',$id)->orWhere('to_lorry_id',$id)->get()->toArray();
            if(count($InventoryTransfer)>0){
                continue;
            }

            $servicedetails = servicedetails::where('from_lorry_id',$id)->orWhere('to_lorry_id',$id)->get()->toArray();
            if(count($servicedetails)>0){
                continue;
            }

            $count = $count + Lorry::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];

        $count = Lorry::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
}
