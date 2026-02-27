<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->datetime('date');
            $table->bigInteger('driver_id', false);
            $table->bigInteger('kelindan_id', false);
            $table->bigInteger('lorry_id', false);
            $table->bigInteger('type', false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('trips');
    }
}
