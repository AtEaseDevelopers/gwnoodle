<?php

namespace App\Http\Controllers;

use App\DataTables\UserDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Repositories\UserRepository;
use App\Repositories\UserHasRoleRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends AppBaseController
{
    /** @var UserRepository $userRepository*/
    private $userRepository;
    private $userHasRoleRepository;

    public function __construct(UserRepository $userRepo, UserHasRoleRepository $userHasRoleRepo)
    {
        $this->userRepository = $userRepo;
        $this->userHasRoleRepository = $userHasRoleRepo;
    }

    /**
     * Display a listing of the User.
     *
     * @param UserDataTable $userDataTable
     *
     * @return Response
     */
    public function index(UserDataTable $userDataTable)
    {
        return $userDataTable->render('users.index');
    }

    /**
     * Show the form for creating a new User.
     *
     * @return Response
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Store a newly created User in storage.
     *
     * @param CreateUserRequest $request
     *
     * @return Response
     */
    public function store(CreateUserRequest $request)
    {
        $input = $request->all();

        $input['password'] = Hash::make($input['password']);
        $user = $this->userRepository->create($input);  


        $userRole = [
            "model_id" => $user["id"],
            "role_id" => $input["role_id"]
        ];

        $userHasRole = $this->userHasRoleRepository->create($userRole);

        Flash::success('User saved successfully.');

        return redirect(route('users.index'));
    }

    /**
     * Display the specified User.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $user = $this->userRepository->find($id);

        if (empty($user)) {
            Flash::error('User not found');

            return redirect(route('users.index'));
        }
        
        $userHasRole = $this->userHasRoleRepository->where('model_id', $id)->first();  // Use Eloquent 'where' and 'first'

        $user->role_name = $userHasRole->role->name ?? "";

        return view('users.show')->with('user', $user);
    }

    /**
     * Show the form for editing the specified User.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $user = $this->userRepository->find($id);

        if (empty($user)) {
            Flash::error('User not found');

            return redirect(route('users.index'));
        }
        
        $userHasRole = $this->userHasRoleRepository->where('model_id', $id)->first();  // Use Eloquent 'where' and 'first'

        $user->role_id = $userHasRole->role_id ?? "";

        return view('users.edit')->with('user', $user);
    }

    /**
     * Update the specified User in storage.
     *
     * @param int $id
     * @param UpdateUserRequest $request
     *
     * @return Response
     */
    public function update($id, Request $request)
    {
        // Decrypt the ID first
        $id = Crypt::decrypt($id);
        
        // Define validation rules
        $rules = [
            'name'     => 'required',
            'email'    => 'required|email|unique:users,email,' . $id,
            'password' => 'confirmed',
            'role_id'  => 'required'
        ];
        
        // Validate the request
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $input = $request->all();
        $user = $this->userRepository->find($id);
        
        if (empty($user)) {
            Flash::error('User not found');
            return redirect(route('Managerusers.index'));
        }
        
        // Only hash and update password if update_password is checked
        if (isset($input['update_password']) && $input['update_password'] == 1) {
            $input['password'] = Hash::make($input['password']);
        } else {
            // Remove password from input to prevent updating with empty value
            unset($input['password']);
        }
        
        // Remove the update_password checkbox value from input
        unset($input['update_password']);
        
        $user = $this->userRepository->update($input, $id);
        
        $userHasRole = $this->userHasRoleRepository->where('model_id', $id)->first();
        
        if($userHasRole)
        {
            $userRole = [
                "role_id" => $input["role_id"]
            ];
            
            $this->userHasRoleRepository->update($userRole, $userHasRole->id);
        }
        else
        {
            $userRole = [
                "model_id" => $user["id"],
                "role_id" => $input["role_id"]
            ];
            
            $userHasRole = $this->userHasRoleRepository->create($userRole);
        }
        
        Flash::success('User updated successfully.');
        
        return redirect(route('Managerusers.index'));
    }

    /**
     * Remove the specified User from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $user = $this->userRepository->find($id);

        if (empty($user)) {
            Flash::error('User not found');

            return redirect(route('users.index'));
        }

        $this->userRepository->delete($id);

        Flash::success('User deleted successfully.');

        return redirect(route('users.index'));
    }
}
