<?php

namespace App\Http\Controllers;

use App\DataTables\UserHasRoleDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateUserHasRoleRequest;
use App\Http\Requests\UpdateUserHasRoleRequest;
use App\Repositories\UserHasRoleRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;

class UserHasRoleController extends AppBaseController
{
    /** @var UserHasRoleRepository $userHasRoleRepository*/
    private $userHasRoleRepository;

    public function __construct(UserHasRoleRepository $userHasRoleRepo)
    {
        $this->userHasRoleRepository = $userHasRoleRepo;
    }

    /**
     * Display a listing of the UserHasRole.
     *
     * @param UserHasRoleDataTable $userHasRoleDataTable
     *
     * @return Response
     */
    public function index(UserHasRoleDataTable $userHasRoleDataTable)
    {
        return $userHasRoleDataTable->render('user_has_roles.index');
    }

    /**
     * Show the form for creating a new UserHasRole.
     *
     * @return Response
     */
    public function create()
    {
        return view('user_has_roles.create');
    }

    /**
     * Store a newly created UserHasRole in storage.
     *
     * @param CreateUserHasRoleRequest $request
     *
     * @return Response
     */
    public function store(CreateUserHasRoleRequest $request)
    {
        $input = $request->all();

        $userHasRole = $this->userHasRoleRepository->create($input);

        Flash::success('User Role saved successfully.');

        return redirect(route('userHasRoles.index'));
    }

    /**
     * Display the specified UserHasRole.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $userHasRole = $this->userHasRoleRepository->find($id);

        if (empty($userHasRole)) {
            Flash::error('User Role not found');

            return redirect(route('userHasRoles.index'));
        }

        return view('user_has_roles.show')->with('userHasRole', $userHasRole);
    }

    /**
     * Show the form for editing the specified UserHasRole.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $userHasRole = $this->userHasRoleRepository->find($id);

        if (empty($userHasRole)) {
            Flash::error('User Role not found');

            return redirect(route('userHasRoles.index'));
        }

        return view('user_has_roles.edit')->with('userHasRole', $userHasRole);
    }

    /**
     * Update the specified UserHasRole in storage.
     *
     * @param int $id
     * @param UpdateUserHasRoleRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateUserHasRoleRequest $request)
    {
        $id = Crypt::decrypt($id);
        $userHasRole = $this->userHasRoleRepository->find($id);

        if (empty($userHasRole)) {
            Flash::error('User Role not found');

            return redirect(route('userHasRoles.index'));
        }

        $userHasRole = $this->userHasRoleRepository->update($request->all(), $id);

        Flash::success('User Role updated successfully.');

        return redirect(route('userHasRoles.index'));
    }

    /**
     * Remove the specified UserHasRole from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $userHasRole = $this->userHasRoleRepository->find($id);

        if (empty($userHasRole)) {
            Flash::error('User Role not found');

            return redirect(route('userHasRoles.index'));
        }

        $this->userHasRoleRepository->delete($id);

        Flash::success('User Role deleted successfully.');

        return redirect(route('userHasRoles.index'));
    }
}
