<?php

namespace App\Http\Controllers;

use App\DataTables\RoleHasPermissionDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateRoleHasPermissionRequest;
use App\Http\Requests\UpdateRoleHasPermissionRequest;
use App\Repositories\RoleHasPermissionRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;

class RoleHasPermissionController extends AppBaseController
{
    /** @var RoleHasPermissionRepository $roleHasPermissionRepository*/
    private $roleHasPermissionRepository;

    public function __construct(RoleHasPermissionRepository $roleHasPermissionRepo)
    {
        $this->roleHasPermissionRepository = $roleHasPermissionRepo;
    }

    /**
     * Display a listing of the RoleHasPermission.
     *
     * @param RoleHasPermissionDataTable $roleHasPermissionDataTable
     *
     * @return Response
     */
    public function index(RoleHasPermissionDataTable $roleHasPermissionDataTable)
    {
        return $roleHasPermissionDataTable->render('role_has_permissions.index');
    }

    /**
     * Show the form for creating a new RoleHasPermission.
     *
     * @return Response
     */
    public function create()
    {
        return view('role_has_permissions.create');
    }

    /**
     * Store a newly created RoleHasPermission in storage.
     *
     * @param CreateRoleHasPermissionRequest $request
     *
     * @return Response
     */
    public function store(CreateRoleHasPermissionRequest $request)
    {
        $input = $request->all();

        $roleHasPermission = $this->roleHasPermissionRepository->create($input);

        Flash::success('Role Permission saved successfully.');

        return redirect(route('roleHasPermissions.index'));
    }

    /**
     * Display the specified RoleHasPermission.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $roleHasPermission = $this->roleHasPermissionRepository->find($id);

        if (empty($roleHasPermission)) {
            Flash::error('Role Permission not found');

            return redirect(route('roleHasPermissions.index'));
        }

        return view('role_has_permissions.show')->with('roleHasPermission', $roleHasPermission);
    }

    /**
     * Show the form for editing the specified RoleHasPermission.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $roleHasPermission = $this->roleHasPermissionRepository->find($id);

        if (empty($roleHasPermission)) {
            Flash::error('Role Permission not found');

            return redirect(route('roleHasPermissions.index'));
        }

        return view('role_has_permissions.edit')->with('roleHasPermission', $roleHasPermission);
    }

    /**
     * Update the specified RoleHasPermission in storage.
     *
     * @param int $id
     * @param UpdateRoleHasPermissionRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateRoleHasPermissionRequest $request)
    {
        $id = Crypt::decrypt($id);
        $roleHasPermission = $this->roleHasPermissionRepository->find($id);

        if (empty($roleHasPermission)) {
            Flash::error('Role Permission not found');

            return redirect(route('roleHasPermissions.index'));
        }

        $roleHasPermission = $this->roleHasPermissionRepository->update($request->all(), $id);

        Flash::success('Role Permission updated successfully.');

        return redirect(route('roleHasPermissions.index'));
    }

    /**
     * Remove the specified RoleHasPermission from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $roleHasPermission = $this->roleHasPermissionRepository->find($id);

        if (empty($roleHasPermission)) {
            Flash::error('Role Permission not found');

            return redirect(route('roleHasPermissions.index'));
        }

        $this->roleHasPermissionRepository->delete($id);

        Flash::success('Role Permission deleted successfully.');

        return redirect(route('roleHasPermissions.index'));
    }
}
