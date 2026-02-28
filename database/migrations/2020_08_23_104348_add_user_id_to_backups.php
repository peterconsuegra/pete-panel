<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserIdToBackups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
       	Schema::table('backups', function (Blueprint $table) {
			 
			 if (!Schema::hasColumn('backups', 'user_id')) {
   				 $table->integer('user_id')->nullable();
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
