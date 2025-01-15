<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleServiceDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sale_service_details', function (Blueprint $table) {
            $table->engine = 'InnoDB';
			$table->integer('id', true);
            $table->integer('sale_id')->index('sale_id');
            $table->integer('service_id')->index('service_id');
			$table->date('date');
			$table->float('price', 10, 0);
			$table->float('total', 10, 0);
			$table->timestamps(6);
            $table->foreign('sale_id', 'sale_id')->references('id')->on('sales')->onUpdate('RESTRICT')->onDelete('RESTRICT');
            $table->foreign('service_id', 'service_id')->references('id')->on('services')->onUpdate('RESTRICT')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sale_service_details');
    }
}
