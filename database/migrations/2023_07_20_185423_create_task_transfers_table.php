<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskTransfersTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_transfers', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->datetime('date')->nullable();
            $table->bigInteger('from_driver_id', false);
            $table->bigInteger('to_driver_id', false);
            $table->bigInteger('task_id', false);
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
        Schema::drop('task_transfers');
    }
}
