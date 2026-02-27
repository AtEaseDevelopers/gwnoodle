<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    
    public function authenticated(Request $request)
    {
        $user = $request->user(); // Get the authenticated user

        // Fetch user's role and permissions
        $role = $user->roles()->first(); // Assuming a "roles" relationship exists
        $permissions = $role->permissions()->pluck('name')->toArray(); // Get permission names

        // Define redirection routes based on permissions or roles
        if (in_array('inventorybalance', $permissions) && !in_array('invoice', $permissions)) {
            return redirect()->route('inventoryBalances.index'); // Redirect Inventory Admin
        } elseif ($role->name === 'admin') {
            return redirect()->route('invoices.index'); // Redirect Admin
        }

        // Default redirection to home route
        return redirect(RouteServiceProvider::HOME);
    }
}           
