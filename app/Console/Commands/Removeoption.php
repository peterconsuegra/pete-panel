<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use App\Models\Option;
use Log;

class Removeoption extends Command
{
    /**
     * The console command name.
     * php artisan dbkeys --var=DB_DATABASE --key=pixma303
     * @var string
     */
    protected $signature = 'removeoption {--option_name=}  {--option_value=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove option';

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
		$option_value = $this->option('option_value');
		if(isset($option_value)){
			
			$option_value = $this->option('option_value');
			$option_value = preg_replace('/\s+/', '', $option_value);
			$option = Option::where('option_value',$option_value)->get()->first();
			if(isset($option))
				$option->delete();	
			
		}
		
		$option_name = $this->option('option_name');
		if(isset($option_name)){
			$option_name = $this->option('option_name');
			$option_name = preg_replace('/\s+/', '', $option_name);
			$option = Option::where('option_name',$option_name)->get()->first();
			if(isset($option))
				$option->delete();
		}
		
		
    }
    
}
