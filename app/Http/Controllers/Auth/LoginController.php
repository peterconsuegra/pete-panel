<?php

namespace App\Http\Controllers\Auth;
use Illuminate\Routing\Controller;

use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Auth;
use App\Models\User;
use Illuminate\Support\Facades\Redirect;
use Log;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
    protected $redirectTo = '/';
    protected $maxAttempts = 5;   // default is 5
    protected $decayMinutes = 1;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
		
    }
	
    public function showLoginForm(Request $request)
    {
		$pete_demo = env('PETE_DEMO');
		$admin_login = $request->input("admin_login");
		
		if(((User::count() == 0 ) || ($pete_demo == "active")) & ($admin_login !="active")){
			return Redirect::to('/register');
		}
		
        return view('auth.login');
    }

    protected function validateLogin(Request $request)
    {
        // Base rules (same as the trait)
        $request->validate([
            $this->username() => 'required|string',
            'password'        => 'required|string',
        ]);

        // --- Simple anti-bot checks ---

        // 1) Honeypot: must be empty
        if ($request->filled('website')) {
            throw ValidationException::withMessages([
                $this->username() => [__('These credentials do not match our records.')],
            ]);
        }

        // 2) Time-trap: require at least 2 seconds between render and submit
        $started = (int) $request->input('form_start_ts', 0);
        $elapsed = time() - $started;

        if ($started === 0 || $elapsed < 2) { // tweak to 3–5s if you like
            throw ValidationException::withMessages([
                $this->username() => [__('These credentials do not match our records.')],
            ]);
        }
    }
	
}
