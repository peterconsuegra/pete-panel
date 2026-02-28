<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeletedToSites extends Migration
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
			    $table->boolean('deleted')->nullable();;
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
