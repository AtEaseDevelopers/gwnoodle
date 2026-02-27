<?php

namespace App\Http\Controllers;

use App\DataTables\focDataTable;
use App\Http\Requests;
use App\Http\Requests\CreatefocRequest;
use App\Http\Requests\UpdatefocRequest;
use App\Repositories\focRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\foc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class focController extends AppBaseController
{
    /** @var focRepository $focRepository*/
    private $focRepository;

    public function __construct(focRepository $focRepo)
    {
        $this->focRepository = $focRepo;
    }

    /**
     * Display a listing of the foc.
     *
     * @param focDataTable $focDataTable
     *
     * @return Response
     */
    public function index(focDataTable $focDataTable)
    {
        return $focDataTable->render('focs.index');
    }

    /**
     * Show the form for creating a new foc.
     *
     * @return Response
     */
    public function create()
    {
        return view('focs.create');
    }

    /**
     * Store a newly created foc in storage.
     *
     * @param CreatefocRequest $request
     *
     * @return Response
     */
    public function store(CreatefocRequest $request)
    {
        $input = $request->all();

        $input['startdate'] = date_create($input['startdate']);
        $input['enddate'] = date_create($input['enddate'] . '23:59:59');

        $foc = $this->focRepository->create($input);

        Flash::success(__('focs.foc_saved_successfully'));

        return redirect(route('focs.index'));
    }

    /**
     * Display the specified foc.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $foc = $this->focRepository->find($id);

        if (empty($foc)) {
            Flash::error(__('focs.foc_not_found'));

            return redirect(route('focs.index'));
        }

        return view('focs.show')->with('foc', $foc);
    }

    /**
     * Show the form for editing the specified foc.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $foc = $this->focRepository->find($id);

        if (empty($foc)) {
            Flash::error(__('focs.foc_not_found'));

            return redirect(route('focs.index'));
        }

        return view('focs.edit')->with('foc', $foc);
    }

    /**
     * Update the specified foc in storage.
     *
     * @param int $id
     * @param UpdatefocRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatefocRequest $request)
    {
        $id = Crypt::decrypt($id);
        $foc = $this->focRepository->find($id);

        if (empty($foc)) {
            Flash::error(__('focs.foc_not_found'));

            return redirect(route('focs.index'));
        }

        $input = $request->all();

        $input['startdate'] = date_create($input['startdate']);
        $input['enddate'] = date_create($input['enddate'] . '23:59:59');

        $foc = $this->focRepository->update($input, $id);

        Flash::success(__('focs.foc_updated_successfully'));

        return redirect(route('focs.index'));
    }

    /**
     * Remove the specified foc from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $foc = $this->focRepository->find($id);

        if (empty($foc)) {
            Flash::error(__('focs.foc_not_found'));

            return redirect(route('focs.index'));
        }

        $this->focRepository->delete($id);

        Flash::success(__('focs.foc_deleted_successfully'));

        return redirect(route('focs.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {

            $foc = $this->focRepository->find($id);

            $count = $count + foc::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];

        $count = foc::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
}
