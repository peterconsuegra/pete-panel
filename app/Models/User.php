<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Services\PeteOption;
use Log;

class User extends Authenticatable
{
    use Notifiable;
	
	protected $fillable = ['name', 'email', 'password','admin','pete_token', 'shared', 'user_db', 'user_db_pass','restored','snapshot','contact_me_again', 'country'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
	
	public function isAdmin(): bool
	{
		return (bool) $this->admin;
	}
	
    public function sites()
	{
          return $this->hasMany('App\Site');
    }
	  
	public static function create_pete_user($data){
		
		$pete_options = new PeteOption();
		$contact_me_again = 0;
		if(isset($data['contact_me_again']))
			$contact_me_again = $data['contact_me_again'];
		
		##Variables
		$save_pete_user_url = ENV('PETE_DASHBOARD_URL')."/save_pete_user_json";
		$os = $pete_options->get_meta_value('os');
		$os_version = $pete_options->get_meta_value('os_version');
		$version = $pete_options->get_meta_value('version');
		$os_distribution = $pete_options->get_meta_value('os_distribution');
		$country = $data['country'];
		
		$pete_data = array(
		"email" => $data['email'], 
		"name" => $data['name'], 
		"os" => $os, "os_version" => $os_version, 
		"version" => $version,
		"os_distribution" => $os_distribution, 
		"country" => $country,
		"contact_me_again" => $contact_me_again
		 );
		
		$options = array(
 
		  'http' => array(
		    'method'  => 'GET',
			'timeout' => 10000,
		    'ignore_errors' => true,
		    'content' => json_encode( $pete_data ),
		    'header'=>  "Content-Type: application/json\r\n" .
		                "Accept: application/json\r\n"
		    )
		);

		$context  = stream_context_create( $options );
		$content = file_get_contents($save_pete_user_url, false, $context );
		$response = json_decode( $content );	
		if($response === FALSE) { 
			Log::info("Dashborad ERROR");
		}
	}
	
}
