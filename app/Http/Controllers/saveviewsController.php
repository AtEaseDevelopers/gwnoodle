<?php

namespace App\Http\Controllers;

use App\DataTables\saveviewsDataTable;
use App\Http\Requests;
use App\Http\Requests\CreatesaveviewsRequest;
use App\Http\Requests\UpdatesaveviewsRequest;
use App\Repositories\saveviewsRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Http\Request;
use App\Models\saveviews;
use Illuminate\Support\Facades\DB;
use \Exception;
use Illuminate\Support\Carbon;

class saveviewsController extends AppBaseController
{
    /** @var saveviewsRepository $saveviewsRepository*/
    private $saveviewsRepository;

    public function __construct(saveviewsRepository $saveviewsRepo)
    {
        $this->saveviewsRepository = $saveviewsRepo;
    }

    /**
     * Display a listing of the saveviews.
     *
     * @param saveviewsDataTable $saveviewsDataTable
     *
     * @return Response
     */
    public function index(saveviewsDataTable $saveviewsDataTable)
    {
        return $saveviewsDataTable->render('saveviews.index');
    }

    /**
     * Show the form for creating a new saveviews.
     *
     * @return Response
     */
    public function create()
    {
        return view('saveviews.create');
    }

    /**
     * Store a newly created saveviews in storage.
     *
     * @param CreatesaveviewsRequest $request
     *
     * @return Response
     */
    public function store(CreatesaveviewsRequest $request)
    {
        $input = $request->all();

        $saveviews = $this->saveviewsRepository->create($input);

        Flash::success('Saveviews saved successfully.');

        return redirect(route('saveviews.index'));
    }

    /**
     * Display the specified saveviews.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $saveviews = $this->saveviewsRepository->find($id);

        if (empty($saveviews)) {
            Flash::error('Saveviews not found');

            return redirect(route('saveviews.index'));
        }

        return view('saveviews.show')->with('saveviews', $saveviews);
    }

    /**
     * Show the form for editing the specified saveviews.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $saveviews = $this->saveviewsRepository->find($id);

        if (empty($saveviews)) {
            Flash::error('Saveviews not found');

            return redirect(route('saveviews.index'));
        }

        return view('saveviews.edit')->with('saveviews', $saveviews);
    }

    /**
     * Update the specified saveviews in storage.
     *
     * @param int $id
     * @param UpdatesaveviewsRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatesaveviewsRequest $request)
    {
        $saveviews = $this->saveviewsRepository->find($id);

        if (empty($saveviews)) {
            Flash::error('Saveviews not found');

            return redirect(route('saveviews.index'));
        }

        $saveviews = $this->saveviewsRepository->update($request->all(), $id);

        Flash::success('Saveviews updated successfully.');

        return redirect(route('saveviews.index'));
    }

    /**
     * Remove the specified saveviews from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $saveviews = $this->saveviewsRepository->find($id);

        if (empty($saveviews)) {
            Flash::error('Saveviews not found');

            return redirect(route('saveviews.index'));
        }

        $this->saveviewsRepository->delete($id);

        Flash::success('Saveviews deleted successfully.');

        return redirect(route('saveviews.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = saveviews::destroy($ids);

        return $count;
    }

    public function view($id)
    {
        if($id == 0){
            abort(404);
        }
        try{
            $result = DB::select('select * from saveviews where id='.$id)[0];
            return view('saveviews.view')->with('result', $result);
        }
        catch(Exception $e){
            abort(404);
        }
    }
}
