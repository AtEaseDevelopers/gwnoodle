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
    
    /**
     * Get the login username to be used by the controller.
     * This overrides the default 'email' field to use 'username'.
     *
     * @return string
     */
    public function username()
    {
        return 'username';
    }
    
    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
    }
    
    /**
     * Attempt to log the user into the application.
     * Override to add status check.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        $credentials = $this->credentials($request);
        
        // Attempt login with credentials
        if ($this->guard()->attempt($credentials, $request->filled('remember'))) {
            $user = $this->guard()->user();
            
            // Check if user status is active
            if (isset($user->status) && $user->status !== 'active') {
                $this->guard()->logout();
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $user = \App\Models\User::where('username', $request->username)->first();
        
        if ($user && isset($user->status) && $user->status !== 'active') {
            return redirect()->back()
                ->withInput($request->only('username', 'remember'))
                ->withErrors([
                    'username' => 'Your account is ' . $user->status . '. Please contact administrator.',
                ]);
        }
        
        return redirect()->back()
            ->withInput($request->only('username', 'remember'))
            ->withErrors([
                'username' => 'These credentials do not match our records.',
            ]);
    }

    public function authenticated(Request $request)
    {
        $user = $request->user(); // Get the authenticated user

        // Check if user has any roles
        if (!$user->roles()->exists()) {
            $this->guard()->logout();
            $request->session()->invalidate();
            return redirect()->route('login')->withErrors([
                'username' => 'No role assigned to this account. Please contact administrator.',
            ]);
        }

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