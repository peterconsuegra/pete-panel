<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDbFieldsToSites extends Migration
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
		  $table->text('db_name')->nullable();
		  $table->text('db_user')->nullable();
		  $table->text('db_password')->nullable();
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
		Schema::table('sites', function ($table) {
		    $table->dropColumn(['db_name', 'db_user', 'db_password']);
		});
    }
}
