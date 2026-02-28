<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLinodeIdToSites extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
       	Schema::table('sites', function (Blueprint $table) {
			
        	if (!Schema::hasColumn('sites', 'bardomain_id')) {
   			 	$table->integer('bardomain_id')->nullable();
         	} 
			
        	if (!Schema::hasColumn('sites', 'linode_id')) {
   			 	$table->integer('linode_id')->nullable();
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
