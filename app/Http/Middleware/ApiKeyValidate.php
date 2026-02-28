<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use Illuminate\Contracts\Auth\Guard;
use Log;
use App\User;
use Crypt;

class ApiKeyValidate
{
  /**
   * Handle an incoming request.
   *
   * @param \Illuminate\Http\Request $request
   * @param \Closure $next
   * @return mixed
   */
  public function handle($request, Closure $next)
  {

	$token = $request->input('pete_token');
	$email = $request->input('email');
	
	/*
	Log::info("PETE TOKEN:");
	Log::info($token);
	
	Log::info("EMAIL:");
	Log::info($email);
	*/
	
	if((!isset($email)) || (!isset($email))){
        return response()->json([
          'status' => 401,
          'message' => 'Acceso no autorizado',
        ], 401);
	}
	
	$user = User::where('email', $email)->first();
	$user_token_decrypted = Crypt::decrypt($user->pete_token);
	
    if (!$token) {
      return response()->json([
        'status' => 401,
        'message' => 'Acceso no autorizado',
      ], 401);
    }

    if ($token) {
      if ($token != $user_token_decrypted) {
        return response()->json([
          'status' => 401,
          'message' => 'Acceso no autorizado',
        ], 401);
      }
    }
	
	Auth::login($user);
    return $next($request);
  }
}