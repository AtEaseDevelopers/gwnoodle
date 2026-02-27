<?php

namespace App\Http\Controllers;

use App\DataTables\KelindanDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateKelindanRequest;
use App\Http\Requests\UpdateKelindanRequest;
use App\Repositories\KelindanRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Kelindan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\Trip;
use App\Models\DriverLocation;

class KelindanController extends AppBaseController
{
    /** @var KelindanRepository $kelindanRepository*/
    private $kelindanRepository;

    public function __construct(KelindanRepository $kelindanRepo)
    {
        $this->kelindanRepository = $kelindanRepo;
    }

    /**
     * Display a listing of the Kelindan.
     *
     * @param KelindanDataTable $kelindanDataTable
     *
     * @return Response
     */
    public function index(KelindanDataTable $kelindanDataTable)
    {
        return $kelindanDataTable->render('kelindans.index');
    }

    /**
     * Show the form for creating a new Kelindan.
     *
     * @return Response
     */
    public function create()
    {
        return view('kelindans.create');
    }

    /**
     * Store a newly created Kelindan in storage.
     *
     * @param CreateKelindanRequest $request
     *
     * @return Response
     */
    public function store(CreateKelindanRequest $request)
    {
        $input = $request->all();
        // if($input['firstvaccine'] != ''){
        //     $input['firstvaccine'] = date_create($input['firstvaccine']);
        // }
        // if($input['secondvaccine'] != ''){
        //     $input['secondvaccine'] = date_create($input['secondvaccine']);
        // }
        // if($input['permitdate'] != ''){
        //     $input['permitdate'] = date_create($input['permitdate']);
        // }
        $kelindan = $this->kelindanRepository->create($input);

        Flash::success($input['name'].__('kelindans.saved_successfully'));

        return redirect(route('kelindans.index'));
    }

    /**
     * Display the specified Kelindan.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $kelindan = $this->kelindanRepository->find($id);

        if (empty($kelindan)) {
            Flash::error(__('kelindans.kelindan_not_found'));

            return redirect(route('kelindans.index'));
        }

        return view('kelindans.show')->with('kelindan', $kelindan);
    }

    /**
     * Show the form for editing the specified Kelindan.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $kelindan = $this->kelindanRepository->find($id);

        if (empty($kelindan)) {
            Flash::error(__('kelindans.kelindan_not_found'));

            return redirect(route('kelindans.index'));
        }

        return view('kelindans.edit')->with('kelindan', $kelindan);
    }

    /**
     * Update the specified Kelindan in storage.
     *
     * @param int $id
     * @param UpdateKelindanRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateKelindanRequest $request)
    {
        $id = Crypt::decrypt($id);
        $kelindan = $this->kelindanRepository->find($id);

        if (empty($kelindan)) {
            Flash::error(__('kelindans.kelindan_not_found'));

            return redirect(route('kelindans.index'));
        }

        $input = $request->all();

        if($input['firstvaccine'] != ''){
            $input['firstvaccine'] = date_create($input['firstvaccine']);
        }
        if($input['secondvaccine'] != ''){
            $input['secondvaccine'] = date_create($input['secondvaccine']);
        }
        if($input['permitdate'] != ''){
            $input['permitdate'] = date_create($input['permitdate']);
        }

        $kelindan = $this->kelindanRepository->update($input, $id);

        Flash::success($kelindan->name.__('kelindans.updated_successfully'));

        return redirect(route('kelindans.index'));
    }

    /**
     * Remove the specified Kelindan from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $kelindan = $this->kelindanRepository->find($id);

        if (empty($kelindan)) {
            Flash::error(__('kelindans.kelindan_not_found'));

            return redirect(route('kelindans.index'));
        }

        $Invoice = Invoice::where('kelindan_id',$id)->get()->toArray();
        if(count($Invoice)>0){
            Flash::error('Unable to delete '.$kelindan->name.', '.$kelindan->name.' is being used in Invoice');

            return redirect(route('kelindans.index'));
        }

        $Trip = Trip::where('kelindan_id',$id)->get()->toArray();
        if(count($Trip)>0){
            Flash::error('Unable to delete '.$kelindan->name.', '.$kelindan->name.' is being used in Trip');

            return redirect(route('kelindans.index'));
        }

        $DriverLocation = DriverLocation::where('kelindan_id',$id)->get()->toArray();
        if(count($DriverLocation)>0){
            Flash::error('Unable to delete '.$kelindan->name.', '.$kelindan->name.' is being used in Driver Location');

            return redirect(route('kelindans.index'));
        }

        $this->kelindanRepository->delete($id);

        Flash::success($kelindan->name.__('kelindans.deleted_successfully'));

        return redirect(route('kelindans.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {

            $Invoice = Invoice::where('kelindan_id',$id)->get()->toArray();
            if(count($Invoice)>0){
                continue;
            }

            $Trip = Trip::where('kelindan_id',$id)->get()->toArray();
            if(count($Trip)>0){
                continue;
            }

            $DriverLocation = DriverLocation::where('kelindan_id',$id)->get()->toArray();
            if(count($DriverLocation)>0){
                continue;
            }

            $kelindan = $this->kelindanRepository->find($id);

            $count = $count + Kelindan::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];

        $count = Kelindan::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
}
