<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBarserverIdToBackups extends Migration
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
			if (!Schema::hasColumn('backups', 'barserver_id')) {
   			 $table->integer('barserver_id')->nullable();
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
