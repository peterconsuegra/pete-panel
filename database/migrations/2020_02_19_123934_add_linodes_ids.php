<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLinodesIds extends Migration
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
			
   			$table->bigInteger('domain_linode_id')->nullable();
   			$table->bigInteger('subdomain_linode_id')->nullable();
         	
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
