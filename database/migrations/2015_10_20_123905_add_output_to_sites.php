<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOutputToSites extends Migration
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
			    $table->longText('output')->nullable();;
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
