<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBackupDaysIdToSites extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //backup_days
       	Schema::table('sites', function (Blueprint $table) {
			if (!Schema::hasColumn('sites', 'backup_days')) {
   			 $table->integer('backup_days')->nullable();
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
