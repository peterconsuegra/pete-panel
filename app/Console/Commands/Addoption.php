<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use App\Models\Option;

class Addoption extends Command
{
    /**
     * The console command name.
     * php artisan addoption --option_name=logs_route --option_value=/Users/peteconsuegra/wwwlog
	 * php artisan addoption --option_name=os_distribution --option_value=darwin
     * @var string
     */
    protected $signature = 'addoption {--option_name=} {--option_value=} {--option_category=} {--option_privileges=} {--option_order=} {--option_version=} {--option_title=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set options for pete';

    /**
     * Execute the console command.
     *
     * @return void
     */
	
    public function __construct()
    {
        parent::__construct();
    }
	
    public function handle()
    {
		
		$option_category = $this->option('option_category');
		$option_name = $this->option('option_name');
		$option_order = $this->option('option_order');
		$option_value = $this->option('option_value');
		$option_privileges = $this->option('option_privileges');
		$option_version = $this->option('option_version');
		$option_title = $this->option('option_title');
		
		if(!isset($option_category)){
			$option_category = "general";
		}
		
		$option = Option::where('option_name',$option_name)->first();
		
		if($option){
			
			if(isset($option_value))
	        	$option->option_value = $option_value;	
			if(isset($option_category))
				$option->category = $option_category;
			if(isset($option_privileges))
				$option->privileges = $option_privileges;
			if(isset($option_order))
				$option->order = $option_order;
			if(isset($option_version))
				$option->version = $option_version;
			if(isset($option_title)){
				$option_title = str_replace("_"," ",$option_title);
				$option->title = $option_title;
			}
		}else{
			
			$option = new Option();
			$option->option_name = $option_name;
			$option->visible = true;
			
			if(isset($option_value))
	        	$option->option_value = $option_value;	
			if(isset($option_category))
				$option->category = $option_category;
			if(isset($option_privileges))
				$option->privileges = $option_privileges;
			if(isset($option_order))
				$option->order = $option_order;
			if(isset($option_version))
				$option->version = $option_version;
			if(isset($option_title)){
				$option_title = str_replace("_"," ",$option_title);
				$option->title = $option_title;
			}
		}
		
		$option->save();	
    }
    
}
