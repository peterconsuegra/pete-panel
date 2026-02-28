<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Models\Site;
use Crypt;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	//admin test user VPS
	//php artisan create_admin --name=pedroconsuegrat --email=pedroconsuegrat@gmail.com --pass=pixma301 --pete_token=nBlAY6LhxZqJfvo9Udmz0GIgk5DKO8
    protected $signature = 'create_admin {--name=} {--email=} {--pass=} {--pete_token=} {--filemanager_info=} {--filemanager_directory=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create admin user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
		$email = $this->option('email');
		$pass = $this->option('pass');
		$name = $this->option('name');
		$pete_token = $this->option('pete_token');
		
		$user = new User();
		$user->admin = true;
		$user->name = $name;
		$user->email = $email;
		$user->password =  bcrypt($pass);
		$user->pete_token = Crypt::encrypt($pete_token);
		$user->filemanager_info = password_hash($this->option('filemanager_info'), PASSWORD_DEFAULT);
		$user->filemanager_directory = $this->option('filemanager_directory');
		$user->save();
	    
    }
}
