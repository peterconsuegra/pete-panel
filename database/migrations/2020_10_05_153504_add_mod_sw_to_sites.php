<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddModSwToSites extends Migration
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
			if (!Schema::hasColumn('sites', 'mod_sw')) {
   			 $table->string('mod_sw')->nullable();
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
