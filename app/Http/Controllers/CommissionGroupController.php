<?php

namespace App\Http\Controllers;

use App\DataTables\CommissionGroupDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateCodeRequest;
use App\Http\Requests\UpdateCodeRequest;
use App\Repositories\CodeRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;

class CommissionGroupController extends AppBaseController
{
    /** @var CodeRepository $codeRepository*/
    private $codeRepository;

    public function __construct(CodeRepository $codeRepo)
    {
        $this->codeRepository = $codeRepo;
    }

    /**
     * Display a listing of the Code.
     *
     * @param CodeDataTable $codeDataTable
     *
     * @return Response
     */
    public function index(CommissionGroupDataTable $codeDataTable)
    {
        return $codeDataTable->render('commission_group.index');
    }

    /**
     * Show the form for creating a new Code.
     *
     * @return Response
     */
    public function create()
    {
        return view('commission_group.create');
    }

    /**
     * Store a newly created Code in storage.
     *
     * @param CreateCodeRequest $request
     *
     * @return Response
     */
    public function store(CreateCodeRequest $request)
    {
        $input = $request->all();

        $code = $this->codeRepository->create($input);

        Flash::success(__('commission.customer_group_saved_successfully'));

        return redirect(route('commission_group.index'));
    }

    /**
     * Display the specified Code.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $code = $this->codeRepository->find($id);

        if (empty($code)) {
            Flash::error(__('commission.commission_group_not_found'));

            return redirect(route('commission_group.index'));
        }

        return view('commission_group.show')->with('code', $code);
    }

    /**
     * Show the form for editing the specified Code.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $code = $this->codeRepository->find($id);

        if (empty($code)) {
            Flash::error(__('commission.commission_group_not_found'));

            return redirect(route('commission_group.index'));
        }

        return view('commission_group.edit')->with('code', $code);
    }

    /**
     * Update the specified Code in storage.
     *
     * @param int $id
     * @param UpdateCodeRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateCodeRequest $request)
    {
        $id = Crypt::decrypt($id);
        $code = $this->codeRepository->find($id);

        if (empty($code)) {
            Flash::error(__('commission.commission_group_not_found'));

            return redirect(route('commission_group.index'));
        }

        $code = $this->codeRepository->update($request->all(), $id);

        Flash::success(__('commission.customer_group_updated_successfully'));

        return redirect(route('commission_group.index'));
    }

    /**
     * Remove the specified Code from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $code = $this->codeRepository->find($id);

        if (empty($code)) {
            Flash::error(__('commission.commission_group_not_found'));

            return redirect(route('commission_group.index'));
        }

        $this->codeRepository->delete($id);

        Flash::success(__('commission.customer_group_deleted_successfully'));

        return redirect(route('commission_group.index'));
    }
}
