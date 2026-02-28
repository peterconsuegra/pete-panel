<?php

namespace App\Models;

use Log;
use Illuminate\Database\Eloquent\Model;
use App\Models\Site;
use App\Services\PeteOption;

class Backup extends Model
{
    //
	
	public function delete_backup(){	
	  $backup_file = $this->get_backup_file();
	  $command = "rm $backup_file";
	  $ouput = shell_exec($command);
	  $this->delete();	 
	  
		if(env('PETE_DEBUG') == "active"){
			Log::info("######DELETE BACKUP DEBUG########");
			Log::info("COMMAND:"); 
			Log::info($command); 
			Log::info("OUTPUT:"); 
			Log::info($ouput); 
		}
	}

	public function file_path(){
		return $this->get_backup_file();
	}
	
	public function get_backup_file(){
		$base_path = base_path();
		$backup_file = $base_path."/backups/$this->name/".$this->file_name;
		return $backup_file;	
	}
	
	
}
