<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Models\Site;
use App\Services\PeteOption;
use App\Models\Backup;
use Log;
use DB;

class ResetDemoSites extends Command
{
   
    protected $signature = 'reset_demo_sites';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset demo Sites';

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
		
		////////////////////////////////
		////WORDPRESS SITES
		////////////////////////////////
		
		$sites = Site::where('url', 'like', '%wordpresspete.com')->get();
		foreach($sites as $site ) {
			if($site->app_name == "WordPress+Laravel"){
				$site->delete_wordpress_laravel();
			}else if($site->app_name == "WordPress"){
				$site->delete_wordpress();
			}
			$site->delete();
	   }

		$trash_sites = Site::onlyTrashed()->orderBy('id', 'desc')->get();	

	    foreach($trash_sites as $site ) {
	 		$site->force_delete_wordpress();
	 		$site->forceDelete();
	    }
		
    }
}
