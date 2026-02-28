<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddWpUserToSitesIfNotExist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
			
	       	Schema::table('sites', function (Blueprint $table) {
				
	        	if (!Schema::hasColumn('sites', 'wp_user')) {
	   			 	$table->string('wp_user')->nullable();
	         	} 
			 
	       });
	   
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
