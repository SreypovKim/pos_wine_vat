<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyFieldToClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clients', function (Blueprint $table) {
			$table->string('email', 192)->nullable()->change();
			$table->string('country')->nullable()->change();
			$table->string('city')->nullable()->change();
			$table->string('adresse')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('email', 192)->change();
			$table->string('country')->change();
			$table->string('city')->change();
			$table->string('adresse')->change();
        });
    }
}
