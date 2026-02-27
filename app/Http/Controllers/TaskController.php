<?php

namespace App\Http\Controllers;

use App\DataTables\TaskDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Repositories\TaskRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TaskController extends AppBaseController
{
    /** @var TaskRepository $taskRepository*/
    private $taskRepository;

    public function __construct(TaskRepository $taskRepo)
    {
        $this->taskRepository = $taskRepo;
    }

    /**
     * Display a listing of the Task.
     *
     * @param TaskDataTable $taskDataTable
     *
     * @return Response
     */
    public function index(TaskDataTable $taskDataTable)
    {
        return $taskDataTable->render('tasks.index');
    }

    /**
     * Show the form for creating a new Task.
     *
     * @return Response
     */
    public function create()
    {
        return view('tasks.create');
    }

    /**
     * Store a newly created Task in storage.
     *
     * @param CreateTaskRequest $request
     *
     * @return Response
     */
    public function store(CreateTaskRequest $request)
    {
        $input = $request->all();

        $input['date'] = date_create($input['date']);
        $input['based'] = 0;

        $task = $this->taskRepository->create($input);

        Flash::success(__('tasks.task_saved_successfully'));

        return redirect(route('tasks.index'));
    }

    /**
     * Display the specified Task.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $task = $this->taskRepository->find($id);

        if (empty($task)) {
            Flash::error(__('tasks.task_not_found'));

            return redirect(route('tasks.index'));
        }

        return view('tasks.show')->with('task', $task);
    }

    /**
     * Show the form for editing the specified Task.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $task = $this->taskRepository->find($id);

        if (empty($task)) {
            Flash::error(__('tasks.task_not_found'));

            return redirect(route('tasks.index'));
        }

        return view('tasks.edit')->with('task', $task);
    }

    /**
     * Update the specified Task in storage.
     *
     * @param int $id
     * @param UpdateTaskRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateTaskRequest $request)
    {
        $id = Crypt::decrypt($id);
        $task = $this->taskRepository->find($id);

        if (empty($task)) {
            Flash::error(__('tasks.task_not_found'));

            return redirect(route('tasks.index'));
        }

        $input = $request->all();

        $input['date'] = date_create($input['date']);

        $task = $this->taskRepository->update($input, $id);

        Flash::success(__('tasks.task_updated_successfully'));

        return redirect(route('tasks.index'));
    }

    /**
     * Remove the specified Task from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $task = $this->taskRepository->find($id);

        if (empty($task)) {
            Flash::error(__('tasks.task_not_found'));

            return redirect(route('tasks.index'));
        }

        $this->taskRepository->delete($id);

        Flash::success(__('tasks.task_deleted_successfully'));

        return redirect(route('tasks.index'));
    }
}
