<?php

namespace App\Http\Controllers;

use App\DataTables\SupervisorDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateSupervisorRequest;
use App\Http\Requests\UpdateSupervisorRequest;
use App\Repositories\SupervisorRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\Invoice;

class SupervisorController extends AppBaseController
{
    /** @var SupervisorRepository $supervisorRepository*/
    private $supervisorRepository;

    public function __construct(SupervisorRepository $supervisorRepo)
    {
        $this->supervisorRepository = $supervisorRepo;
    }

    /**
     * Display a listing of the Supervisor.
     *
     * @param SupervisorDataTable $supervisorDataTable
     *
     * @return Response
     */
    public function index(SupervisorDataTable $supervisorDataTable)
    {
        return $supervisorDataTable->render('supervisors.index');
    }

    /**
     * Show the form for creating a new Supervisor.
     *
     * @return Response
     */
    public function create()
    {
        return view('supervisors.create');
    }

    /**
     * Store a newly created Supervisor in storage.
     *
     * @param CreateSupervisorRequest $request
     *
     * @return Response
     */
    public function store(CreateSupervisorRequest $request)
    {
        $input = $request->all();
        if($input['firstvaccine'] != ''){
            $input['firstvaccine'] = date_create($input['firstvaccine']);
        }
        if($input['secondvaccine'] != ''){
            $input['secondvaccine'] = date_create($input['secondvaccine']);
        }
        $supervisor = $this->supervisorRepository->create($input);

        Flash::success($input['name'].__('operations.task_saved_successfully'));

        return redirect(route('supervisors.index'));
    }

    /**
     * Display the specified Supervisor.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $supervisor = $this->supervisorRepository->find($id);

        if (empty($supervisor)) {
            Flash::error(__('operations.operation_not_found'));

            return redirect(route('supervisors.index'));
        }

        return view('supervisors.show')->with('supervisor', $supervisor);
    }

    /**
     * Show the form for editing the specified Supervisor.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $supervisor = $this->supervisorRepository->find($id);

        if (empty($supervisor)) {
            Flash::error(__('operations.operation_not_found'));

            return redirect(route('supervisors.index'));
        }

        return view('supervisors.edit')->with('supervisor', $supervisor);
    }

    /**
     * Update the specified Supervisor in storage.
     *
     * @param int $id
     * @param UpdateSupervisorRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateSupervisorRequest $request)
    {
        $id = Crypt::decrypt($id);
        $supervisor = $this->supervisorRepository->find($id);

        if (empty($supervisor)) {
            Flash::error(__('operations.operation_not_found'));

            return redirect(route('supervisors.index'));
        }

        $input = $request->all();

        if($input['firstvaccine'] != ''){
            $input['firstvaccine'] = date_create($input['firstvaccine']);
        }
        if($input['secondvaccine'] != ''){
            $input['secondvaccine'] = date_create($input['secondvaccine']);
        }

        $supervisor = $this->supervisorRepository->update($input, $id);

        Flash::success($supervisor->name.__('operations.updated_successfully'));

        return redirect(route('supervisors.index'));
    }

    /**
     * Remove the specified Supervisor from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $supervisor = $this->supervisorRepository->find($id);

        if (empty($supervisor)) {
            Flash::error(__('operations.operation_not_found'));

            return redirect(route('supervisors.index'));
        }

        $customer = Customer::where('supervisor_id',$id)->get()->toArray();
        if(count($customer)>0){
            Flash::error('Unable to delete '.$supervisor->name.', '.$supervisor->name.' is being used in Customer');

            return redirect(route('supervisors.index'));
        }

        $Invoice = Invoice::where('supervisor_id',$id)->get()->toArray();
        if(count($Invoice)>0){
            Flash::error('Unable to delete '.$supervisor->name.', '.$supervisor->name.' is being used in Invoice');

            return redirect(route('supervisors.index'));
        }

        $this->supervisorRepository->delete($id);

        Flash::success($supervisor->name.__('operations.deleted_successfully'));

        return redirect(route('supervisors.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {

            $supervisor = $this->supervisorRepository->find($id);

            $customer = Customer::where('supervisor_id',$id)->get()->toArray();
            if(count($customer)>0){
                continue;
            }

            $Invoice = Invoice::where('supervisor_id',$id)->get()->toArray();
            if(count($Invoice)>0){
                continue;
            }

            $count = $count + Supervisor::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];

        $count = Supervisor::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
}
