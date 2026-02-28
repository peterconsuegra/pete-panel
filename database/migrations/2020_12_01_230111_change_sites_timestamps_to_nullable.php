<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeSitesTimestampsToNullable extends Migration
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
			
			if (!Schema::hasColumn('sites', 'ssl')) {
   			 $table->boolean('ssl')->nullable();
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
