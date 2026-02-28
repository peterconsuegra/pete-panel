<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCmsFieldsToSites extends Migration
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
			    $table->text('cms_user')->nullable();
				$table->text('cms_password')->nullable();
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
