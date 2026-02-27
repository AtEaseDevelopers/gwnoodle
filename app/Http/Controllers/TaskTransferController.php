<?php

namespace App\Http\Controllers;

use App\DataTables\TaskTransferDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateTaskTransferRequest;
use App\Http\Requests\UpdateTaskTransferRequest;
use App\Repositories\TaskTransferRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;

class TaskTransferController extends AppBaseController
{
    /** @var TaskTransferRepository $taskTransferRepository*/
    private $taskTransferRepository;

    public function __construct(TaskTransferRepository $taskTransferRepo)
    {
        $this->taskTransferRepository = $taskTransferRepo;
    }

    /**
     * Display a listing of the TaskTransfer.
     *
     * @param TaskTransferDataTable $taskTransferDataTable
     *
     * @return Response
     */
    public function index(TaskTransferDataTable $taskTransferDataTable)
    {
        return $taskTransferDataTable->render('task_transfers.index');
    }

    /**
     * Show the form for creating a new TaskTransfer.
     *
     * @return Response
     */
    public function create()
    {
        return view('task_transfers.create');
    }

    /**
     * Store a newly created TaskTransfer in storage.
     *
     * @param CreateTaskTransferRequest $request
     *
     * @return Response
     */
    public function store(CreateTaskTransferRequest $request)
    {
        $input = $request->all();

        $taskTransfer = $this->taskTransferRepository->create($input);

        Flash::success(__('task_transfers.task_transfer_saved_successfully'));
        return redirect(route('taskTransfers.index'));
    }

    /**
     * Display the specified TaskTransfer.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $taskTransfer = $this->taskTransferRepository->find($id);

        if (empty($taskTransfer)) {
            Flash::error(__('task_transfers.task_transfer_not_found'));

            return redirect(route('taskTransfers.index'));
        }

        return view('task_transfers.show')->with('taskTransfer', $taskTransfer);
    }

    /**
     * Show the form for editing the specified TaskTransfer.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $taskTransfer = $this->taskTransferRepository->find($id);

        if (empty($taskTransfer)) {
            Flash::error(__('task_transfers.task_transfer_not_found'));

            return redirect(route('taskTransfers.index'));
        }

        return view('task_transfers.edit')->with('taskTransfer', $taskTransfer);
    }

    /**
     * Update the specified TaskTransfer in storage.
     *
     * @param int $id
     * @param UpdateTaskTransferRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateTaskTransferRequest $request)
    {
        $taskTransfer = $this->taskTransferRepository->find($id);

        if (empty($taskTransfer)) {
            Flash::error(__('task_transfers.task_transfer_not_found'));

            return redirect(route('taskTransfers.index'));
        }

        $taskTransfer = $this->taskTransferRepository->update($request->all(), $id);
        Flash::success(__('task_transfers.task_transfer_updated_successfully'));    

        return redirect(route('taskTransfers.index'));
    }

    /**
     * Remove the specified TaskTransfer from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $taskTransfer = $this->taskTransferRepository->find($id);

        if (empty($taskTransfer)) {
            Flash::error(__('task_transfers.task_transfer_not_found'));

            return redirect(route('taskTransfers.index'));
        }

        $this->taskTransferRepository->delete($id);

        Flash::success(__('task_transfers.task_transfer_deleted_successfully'));
        
        return redirect(route('taskTransfers.index'));
    }
}
