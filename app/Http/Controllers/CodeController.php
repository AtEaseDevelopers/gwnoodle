<?php

namespace App\Http\Controllers;

use App\DataTables\CodeDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateCodeRequest;
use App\Http\Requests\UpdateCodeRequest;
use App\Repositories\CodeRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;

class CodeController extends AppBaseController
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
    public function index(CodeDataTable $codeDataTable)
    {
        return $codeDataTable->render('codes.index');
    }

    /**
     * Show the form for creating a new Code.
     *
     * @return Response
     */
    public function create()
    {
        return view('codes.create');
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

        Flash::success(__('codes.code_saved_successfully'));

        return redirect(route('codes.index'));
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
            Flash::error(__('codes.code_not_found'));

            return redirect(route('codes.index'));
        }

        return view('codes.show')->with('code', $code);
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
            Flash::error(__('codes.code_not_found'));

            return redirect(route('codes.index'));
        }

        return view('codes.edit')->with('code', $code);
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
            Flash::error(__('codes.code_not_found'));

            return redirect(route('codes.index'));
        }

        $code = $this->codeRepository->update($request->all(), $id);

        Flash::success(__('codes.code_updated_successfully'));

        return redirect(route('codes.index'));
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
            Flash::error(__('codes.code_not_found'));

            return redirect(route('codes.index'));
        }

        $this->codeRepository->delete($id);

        Flash::success(__('codes.code_deleted_successfully'));

        return redirect(route('codes.index'));
    }
}
