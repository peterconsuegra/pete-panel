<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddToCloneProject extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
	public function up()
	    {
			Schema::table('sites', function($table)
			{
			    $table->string('to_clone_project')->nullable();;
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
