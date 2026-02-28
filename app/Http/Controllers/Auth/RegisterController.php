<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Log;
use View;
use App\Services\PeteOption;
use Illuminate\Http\Request;
use App\Models\User;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    
    public function __construct()
    {
        $this->middleware('guest');
		$pete_demo = env('PETE_DEMO');
		$users = User::orderBy('id', 'desc');
		
		if($pete_demo=="active"){
			$this->middleware('guest', ['except' =>  'getLogout']);
		}else if ($users->count() == 0){
			$this->middleware('guest', ['except' =>  'getLogout']);
		}else{
			$this->middleware('auth', ['except' => array('getLogin', 'postLogin','postRegister')]);
		}
		View::share(compact('pete_demo'));
    }
	
	public function getRegister()
	{
		Log::info("entro en getRegister");
	   return view('auth.register');
	}

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
			'user_name' => ['required', 'string', 'max:255'],
			'country' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
		if(User::count() == 0 ){
	        
			$user = User::create([
				'admin' => true,
	            'name' => $data['name'],
	            'email' => $data['email'],
	            'password' => Hash::make($data['password']),
	        ]);
			
		}else{
			
	        $user = User::create([
	            'name' => $data['name'],
	            'email' => $data['email'],
				"contact_me_again" => $data['contact_me_again'],
	            'password' => Hash::make($data['password']),
	        ]);
			
		}
		
		User::create_pete_user($data);
		
		return $user;
       
    }
}
