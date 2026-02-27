<?php

namespace App\Http\Controllers;

use App\DataTables\DriverDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Repositories\DriverRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\TaskTransfer;
use App\Models\Trip;
use App\Models\InventoryTransfer;
use App\Models\DriverLocation;
use App\Models\Assign;

class DriverController extends AppBaseController
{
    /** @var DriverRepository $driverRepository*/
    private $driverRepository;

    public function __construct(DriverRepository $driverRepo)
    {
        $this->driverRepository = $driverRepo;
    }

    /**
     * Display a listing of the Driver.
     *
     * @param DriverDataTable $driverDataTable
     *
     * @return Response
     */
    public function index(DriverDataTable $driverDataTable)
    {
        return $driverDataTable->render('drivers.index');
    }

    /**
     * Show the form for creating a new Driver.
     *
     * @return Response
     */
    public function create()
    {
        return view('drivers.create');
    }

    /**
     * Store a newly created Driver in storage.
     *
     * @param CreateDriverRequest $request
     *
     * @return Response
     */
    public function store(CreateDriverRequest $request)
    {
        $input = $request->all();
    
        $driver = $this->driverRepository->create($input);

        Flash::success($input['name'].__('drivers.saved_successfully'));

        return redirect(route('drivers.index'));
    }

    /**
     * Display the specified Driver.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $driver = $this->driverRepository->find($id);

        if (empty($driver)) {
            Flash::error(__('drivers.driver_not_found'));

            return redirect(route('drivers.index'));
        }

        $assign = Assign::with('customer')->where('driver_id',$id)->get()->toArray();
        return view('drivers.show')->with('driver', $driver)->with('assign',$assign)->with('id',$id);
    }

    /**
     * Show the form for editing the specified Driver.
     *
     * @param int $id
     *
    * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $driver = $this->driverRepository->find($id);

    if (empty($driver)) {
            Flash::error(__('drivers.driver_not_found'));

            return redirect(route('drivers.index'));
        }

        return view('drivers.edit')->with('driver', $driver);
    }

    /**
     * Update the specified Driver in storage.
     *
     * @param int $id
     * @param UpdateDriverRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateDriverRequest $request)
    {
        $id = Crypt::decrypt($id);
        $driver = $this->driverRepository->find($id);

        if (empty($driver)) {
            Flash::error(__('drivers.driver_not_found'));

            return redirect(route('drivers.index'));
        }

        $input = $request->all();

        $driver = $this->driverRepository->update($input, $id);

        Flash::success($driver->name.__('drivers.updated_successfully'));

        return redirect(route('drivers.index'));
    }

    /**
     * Remove the specified Driver from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $driver = $this->driverRepository->find($id);

        if (empty($driver)) {
            Flash::error(__('drivers.driver_not_found'));

            return redirect(route('drivers.index'));
        }

        $Trip = Trip::where('driver_id',$id)->get()->toArray();
        if(count($Trip)>0){
            Flash::error('Unable to delete '.$driver->name.', '.$driver->name.' is being used in Trip');

            return redirect(route('drivers.index'));
        }

        $Invoice = Invoice::where('driver_id',$id)->get()->toArray();
        if(count($Invoice)>0){
            Flash::error('Unable to delete '.$driver->name.', '.$driver->name.' is being used in Invoice');

            return redirect(route('drivers.index'));
        }

        $Task = Task::where('driver_id',$id)->get()->toArray();
        if(count($Task)>0){
            Flash::error('Unable to delete '.$driver->name.', '.$driver->name.' is being used in Task');

            return redirect(route('drivers.index'));
        }

        $TaskTransfer = TaskTransfer::where('from_driver_id',$id)->orWhere('to_driver_id',$id)->get()->toArray();
        if(count($TaskTransfer)>0){
            Flash::error('Unable to delete '.$driver->name.', '.$driver->name.' is being used in Task Transfer');

            return redirect(route('drivers.index'));
        }

        $InventoryTransfer = InventoryTransfer::where('from_driver_id',$id)->orWhere('to_driver_id',$id)->get()->toArray();
        if(count($InventoryTransfer)>0){
            Flash::error('Unable to delete '.$driver->name.', '.$driver->name.' is being used in Inventory Transfer');

            return redirect(route('drivers.index'));
        }

        $DriverLocation = DriverLocation::where('driver_id',$id)->get()->toArray();
        if(count($DriverLocation)>0){
            Flash::error('Unable to delete '.$driver->name.', '.$driver->name.' is being used in Driver Location');

            return redirect(route('drivers.index'));
        }

        $Assign = Assign::where('driver_id',$id)->get()->toArray();
        if(count($Assign)>0){
            Flash::error('Unable to delete '.$driver->name.', '.$driver->name.' is being used in Assign');

            return redirect(route('drivers.index'));
        }

        $this->driverRepository->delete($id);

        Flash::success($driver->name.__('drivers.deleted_successfully'));

        return redirect(route('drivers.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {

            $Trip = Trip::where('driver_id',$id)->get()->toArray();
            if(count($Trip)>0){
                continue;
            }

            $Invoice = Invoice::where('driver_id',$id)->get()->toArray();
            if(count($Invoice)>0){
                continue;
            }

            $Task = Task::where('driver_id',$id)->get()->toArray();
            if(count($Task)>0){
                continue;
            }

            $TaskTransfer = TaskTransfer::where('from_driver_id',$id)->orWhere('to_driver_id',$id)->get()->toArray();
            if(count($TaskTransfer)>0){
                continue;
            }

            $InventoryTransfer = InventoryTransfer::where('from_driver_id',$id)->orWhere('to_driver_id',$id)->get()->toArray();
            if(count($InventoryTransfer)>0){
                continue;
            }

            $DriverLocation = DriverLocation::where('driver_id',$id)->get()->toArray();
            if(count($DriverLocation)>0){
                continue;
            }

            $Assign = Assign::where('driver_id',$id)->get()->toArray();
            if(count($Assign)>0){
                continue;
            }

            $driver = $this->driverRepository->find($id);

            $count = $count + Driver::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];

        $count = Driver::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }

    public function assign($id)
    {
        $id = Crypt::decrypt($id);
        $driver = $this->driverRepository->find($id);

        if (empty($driver)) {
            Flash::error(__('drivers.driver_not_found'));

            return redirect(route('drivers.index'));
        }

        return view('drivers.assign')->with('id', $id);
    }

    public function addassign($id , Request $request)
    {
        $id = Crypt::decrypt($id);

        $input = $request->all();

        $messages = array(
            'customer_id.required' => 'Customer is required',
            'sequence.required' => 'Sequence is required',
        );
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'sequence' => 'required',
        ], $messages);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $driver = $this->driverRepository->find($id);

        if (empty($driver)) {
            Flash::error(__('drivers.driver_not_found'));

            return redirect(route('drivers.index'));
        }

        $assign = new Assign();
        $assign->driver_id = $id;
        $assign->customer_id = $input['customer_id'];
        $assign->sequence = $input['sequence'];
        $assign->save();

        Flash::success(__('drivers.assign_saved_successfully'));

        return redirect(route('drivers.show',encrypt($id)));
    }

    public function deleteassign($id)
    {
        $id = Crypt::decrypt($id);

        $assign = Assign::where('id',$id)->first();

        if (empty($assign)) {
            Flash::error(__('drivers.assign_not_found'));

            return redirect()->back();
        }

        $assign->delete($id);

        Flash::success(__('drivers.assign_deleted_successfully'));

        return redirect(route('drivers.show',encrypt($assign->driver_id)));
    }

}
