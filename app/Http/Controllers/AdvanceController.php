<?php

namespace App\Http\Controllers;

use App\DataTables\AdvanceDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateAdvanceRequest;
use App\Http\Requests\UpdateAdvanceRequest;
use App\Repositories\AdvanceRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use App\Models\Advance;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Code;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdvanceController extends AppBaseController
{
    /** @var AdvanceRepository $advanceRepository*/
    private $advanceRepository;

    public function __construct(AdvanceRepository $advanceRepo)
    {
        $this->advanceRepository = $advanceRepo;
    }

    /**
     * Display a listing of the Advance.
     *
     * @param AdvanceDataTable $advanceDataTable
     *
     * @return Response
     */
    public function index(AdvanceDataTable $advanceDataTable)
    {
        return $advanceDataTable->render('advances.index');
    }

    /**
     * Show the form for creating a new Advance.
     *
     * @return Response
     */
    public function create()
    {
        return view('advances.create');
    }

    /**
     * Store a newly created Advance in storage.
     *
     * @param CreateAdvanceRequest $request
     *
     * @return Response
     */
    public function store(CreateAdvanceRequest $request)
    {
        $input = $request->all();
        
        if($input['no'] == null){
            $advance_runningno = Code::where('code','advance_runningno')->select('value')->get()[0]['value'];
            $input['no'] = 'A'.date("y").date("m").date("d").'_'.$advance_runningno;
            Code::where('code','advance_runningno')->update(['value' => $advance_runningno + 1]);
        }

        $input['date'] = date_create($input['date']);
        $advance = $this->advanceRepository->create($input);

        Flash::success('Advance saved successfully.');

        return redirect(route('advances.index'));
    }

    /**
     * Display the specified Advance.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $advance = $this->advanceRepository->find($id);

        if (empty($advance)) {
            Flash::error('Advance not found');

            return redirect(route('advances.index'));
        }

        return view('advances.show')->with('advance', $advance);
    }

    /**
     * Show the form for editing the specified Advance.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $advance = $this->advanceRepository->find($id);

        if (empty($advance)) {
            Flash::error('Advance not found');

            return redirect(route('advances.index'));
        }

        return view('advances.edit')->with('advance', $advance);
    }

    /**
     * Update the specified Advance in storage.
     *
     * @param int $id
     * @param UpdateAdvanceRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAdvanceRequest $request)
    {
        $id = Crypt::decrypt($id);
        $advance = $this->advanceRepository->find($id);

        if (empty($advance)) {
            Flash::error('Advance not found');

            return redirect(route('advances.index'));
        }
        
        $input = $request->all();

        $input['date'] = date_create($input['date']);
        $advance = $this->advanceRepository->update($input, $id);

        Flash::success('Advance updated successfully.');

        return redirect(route('advances.index'));
    }

    /**
     * Remove the specified Advance from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $advance = $this->advanceRepository->find($id);

        if (empty($advance)) {
            Flash::error('Advance not found');

            return redirect(route('advances.index'));
        }

        $this->advanceRepository->delete($id);

        Flash::success('Advance deleted successfully.');

        return redirect(route('advances.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewAdvance('.Auth::id().',\'Advances\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = Advance::destroy($ids);

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = Advance::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
}
