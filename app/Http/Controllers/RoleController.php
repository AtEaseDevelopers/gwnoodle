<?php

namespace App\Http\Controllers;

use App\DataTables\RoleDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Repositories\RoleRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Repositories\RoleHasPermissionRepository;
use Illuminate\Support\Facades\DB;

class RoleController extends AppBaseController
{
    /** @var RoleRepository $roleRepository*/
    private $roleRepository;
    private $roleHasPermissionRepository;


    public function __construct(RoleRepository $roleRepo, RoleHasPermissionRepository $roleHasPermissionRepo)
    {
        $this->roleRepository = $roleRepo;
        $this->roleHasPermissionRepository = $roleHasPermissionRepo;
    }

    /**
     * Display a listing of the Role.
     *
     * @param RoleDataTable $roleDataTable
     *
     * @return Response
     */
    public function index(RoleDataTable $roleDataTable)
    {
        return $roleDataTable->render('roles.index');
    }

    /**
     * Show the form for creating a new Role.
     *
     * @return Response
     */
    public function create()
    {
        return view('roles.create');
    }

    /**
     * Store a newly created Role in storage.
     *
     * @param CreateRoleRequest $request
     *
     * @return Response
     */
    public function store(CreateRoleRequest $request)
    {
        $input = $request->all();

        $permissionIds = $request->input('permission_id', []);
        unset($input['permission_id']);
        $role = $this->roleRepository->create($input);
        // attach each permission
        foreach ($permissionIds as $permId) {
            $this->roleHasPermissionRepository->create([
                'role_id' => $role->id,
                'permission_id' => $permId,
            ]);
        }

        Flash::success(__('role.role_saved_successfully'));

        return redirect(route('roles.index'));
    }

    /**
     * Display the specified Role.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $role = $this->roleRepository->find($id);

        if (empty($role)) {
            Flash::error(__('role.role_not_found'));

            return redirect(route('roles.index'));
        }

        $roleHasPermission = $this->roleHasPermissionRepository->where('role_id', $id)->first();  // Use Eloquent 'where' and 'first'

        $role->permission_name = $roleHasPermission->permission->name ?? "";


        return view('roles.show')->with('role', $role);
    }

    /**
     * Show the form for editing the specified Role.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $role = $this->roleRepository->find($id);

        if (empty($role)) {
            Flash::error(__('role.role_not_found'));

            return redirect(route('roles.index'));
        }
        
        $roleHasPermission = $this->roleHasPermissionRepository->where('role_id', $id)->get();  // Use Eloquent 'where' and 'first'

        $role->permissions = $roleHasPermission->pluck('permission_id')->toArray();

        return view('roles.edit')->with('role', $role);
    }

    /**
     * Update the specified Role in storage.
     *
     * @param int $id
     * @param UpdateRoleRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateRoleRequest $request)
    {
        $id = Crypt::decrypt($id);
        $role = $this->roleRepository->find($id);

        if (empty($role)) {
            Flash::error(__('role.role_not_found'));

            return redirect(route('roles.index'));
        }

        $role = $this->roleRepository->update($request->all(), $id);

        // sync permissions: remove old and add new
        DB::table('role_has_permissions')->where('role_id', $id)->delete();
        $permissionIds = $request->input('permission_id', []);
        foreach ($permissionIds as $permId) {
            $this->roleHasPermissionRepository->create([
                'role_id' => $id,
                'permission_id' => $permId,
            ]);
        }

        Flash::success(__('role.role_updated_successfully'));

        return redirect(route('roles.index'));
    }

    /**
     * Remove the specified Role from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $role = $this->roleRepository->find($id);

        if (empty($role)) {
            Flash::error(__('role.role_not_found'));

            return redirect(route('roles.index'));
        }

        $this->roleRepository->delete($id);

        DB::table('role_has_permissions')->where('role_id', $id)->delete();

        Flash::success(__('role.role_deleted_successfully'));

        return redirect(route('roles.index'));
    }
}
