<?php

namespace App\Http\Controllers;

use App\DataTables\CompoundDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateCompoundRequest;
use App\Http\Requests\UpdateCompoundRequest;
use App\Repositories\CompoundRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Code;
use Illuminate\Http\Request;
use App\Models\Compound;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class CompoundController extends AppBaseController
{
    /** @var CompoundRepository $compoundRepository*/
    private $compoundRepository;

    public function __construct(CompoundRepository $compoundRepo)
    {
        $this->compoundRepository = $compoundRepo;
    }

    /**
     * Display a listing of the Compound.
     *
     * @param CompoundDataTable $compoundDataTable
     *
     * @return Response
     */
    public function index(CompoundDataTable $compoundDataTable)
    {
        return $compoundDataTable->render('compounds.index');
    }

    /**
     * Show the form for creating a new Compound.
     *
     * @return Response
     */
    public function create()
    {
        return view('compounds.create');
    }

    /**
     * Store a newly created Compound in storage.
     *
     * @param CreateCompoundRequest $request
     *
     * @return Response
     */
    public function store(CreateCompoundRequest $request)
    {
        $input = $request->all();

        if($input['no'] == null){
            $compound_runningno = Code::where('code','compound_runningno')->select('value')->get()[0]['value'];
            $input['no'] = 'P'.date("y").date("m").date("d").'_'.$compound_runningno;
            Code::where('code','compound_runningno')->update(['value' => $compound_runningno + 1]);
        }
        if($input['driver_id'] == null){
            $input['status'] = 1;
        }
        $input['date'] = date_create($input['date']);
        $compound = $this->compoundRepository->create($input);

        Flash::success('Compound saved successfully.');

        return redirect(route('compounds.index'));
    }

    /**
     * Display the specified Compound.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $compound = $this->compoundRepository->find($id);

        if (empty($compound)) {
            Flash::error('Compound not found');

            return redirect(route('compounds.index'));
        }

        return view('compounds.show')->with('compound', $compound);
    }

    /**
     * Show the form for editing the specified Compound.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $compound = $this->compoundRepository->find($id);

        if (empty($compound)) {
            Flash::error('Compound not found');

            return redirect(route('compounds.index'));
        }

        return view('compounds.edit')->with('compound', $compound);
    }

    /**
     * Update the specified Compound in storage.
     *
     * @param int $id
     * @param UpdateCompoundRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateCompoundRequest $request)
    {
        $id = Crypt::decrypt($id);
        $compound = $this->compoundRepository->find($id);

        if (empty($compound)) {
            Flash::error('Compound not found');

            return redirect(route('compounds.index'));
        }

        $input = $request->all();

        if($input['driver_id'] == null){
            $input['status'] = 1;
        }
        $input['date'] = date_create($input['date']);
        $compound = $this->compoundRepository->update($input, $id);

        Flash::success('Compound updated successfully.');

        return redirect(route('compounds.index'));
    }

    /**
     * Remove the specified Compound from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $compound = $this->compoundRepository->find($id);

        if (empty($compound)) {
            Flash::error('Compound not found');

            return redirect(route('compounds.index'));
        }

        $this->compoundRepository->delete($id);

        Flash::success('Compound deleted successfully.');

        return redirect(route('compounds.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewCompound('.Auth::id().',\'Compounds\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = Compound::destroy($ids);

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = Compound::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }

    public function getLorryPermitHolder(Request $request)
    {
        $data = $request->all();
        $lorry_id = $data['lorry_id'];
        $data = DB::select('select l.permitholder from lorrys l where l.id = \''.$lorry_id.'\';');
        if(empty($data)){
            $result = '';
        }
        else{
            $result = $data[0]->permitholder;
        }
        return $result;
    }

}
