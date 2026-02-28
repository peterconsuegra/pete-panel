<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddContactMeAgainToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    
       	Schema::table('users', function (Blueprint $table) {
			
			if (!Schema::hasColumn('users', 'contact_me_again')) {
   			 	$table->boolean('contact_me_again')->nullable();
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
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
