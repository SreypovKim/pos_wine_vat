<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCombinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('combines', function (Blueprint $table) {
            $table->id();
         	$table->integer('product_id')->nullable()->index('product_id_combine');
            $table->integer('product_combine_id')->nullable()->index('product_combine_id');
			$table->decimal('qty')->nullable()->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('combines');
    }
}
