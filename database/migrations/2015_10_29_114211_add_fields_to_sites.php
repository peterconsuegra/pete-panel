<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsToSites extends Migration
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
			    $table->string('app_name')->nullable();
				$table->string('action_name')->nullable();
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
