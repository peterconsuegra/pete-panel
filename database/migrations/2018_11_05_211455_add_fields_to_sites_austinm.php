<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldsToSitesAustinm extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
		Schema::table('sites', function($table)
		{
			  #$table->integer('barserver_id')->nullable();
			  #$table->string('db_user_pass')->nullable();
			  #$table->string('wp_user')->nullable();
			  #$table->string('wp_pass')->nullable();
			  
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
