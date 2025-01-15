<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDoctorSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
           $table->engine = 'InnoDB';
			$table->integer('id', true);
			$table->integer('user_id')->index('user_id')->nullable();
            $table->integer('sun')->nullable();
            $table->time('sun_start_time')->nullable()->default(null);
            $table->time('sun_end_time')->nullable()->default(null);
            $table->integer('mon')->nullable();
            $table->time('mon_start_time')->nullable()->default(null);
            $table->time('mon_end_time')->nullable()->default(null);
            $table->integer('tue')->nullable();
            $table->time('tue_start_time')->nullable()->default(null);
            $table->time('tue_end_time')->nullable()->default(null);
            $table->integer('wed')->nullable();
            $table->time('wed_start_time')->nullable()->default(null);
            $table->time('wed_end_time')->nullable()->default(null);
            $table->integer('thu')->nullable();
            $table->time('thu_start_time')->nullable()->default(null);
            $table->time('thu_end_time')->nullable()->default(null);
            $table->integer('fri')->nullable();
            $table->time('fri_start_time')->nullable()->default(null);
            $table->time('fri_end_time')->nullable()->default(null);
            $table->integer('sat')->nullable();
            $table->time('sat_start_time')->nullable()->default(null);
            $table->time('sat_end_time')->nullable()->default(null);
            $table->foreign('user_id', 'user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->timestamps(6);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doctor_schedules');
    }
}
